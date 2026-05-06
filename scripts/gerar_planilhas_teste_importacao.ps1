$ErrorActionPreference = "Stop"

function Escape-Xml {
    param([string]$Value)
    return [System.Security.SecurityElement]::Escape($Value)
}

function Get-ColLetter {
    param([int]$Index)
    $letters = ""
    while ($Index -gt 0) {
        $mod = ($Index - 1) % 26
        $letters = [char](65 + $mod) + $letters
        $Index = [int](($Index - 1) / 26)
    }
    return $letters
}

function New-XlsxFile {
    param(
        [string]$OutputPath,
        [string[]]$Header,
        [object[][]]$Rows
    )

    $allRows = @($Header) + $Rows
    $sheetRows = New-Object System.Collections.Generic.List[string]

    for ($r = 0; $r -lt $allRows.Count; $r++) {
        $rowNumber = $r + 1
        $cells = New-Object System.Collections.Generic.List[string]
        $row = $allRows[$r]

        for ($c = 0; $c -lt $row.Count; $c++) {
            $cellRef = "$(Get-ColLetter ($c + 1))$rowNumber"
            $value = [string]$row[$c]
            $cells.Add("<c r=`"$cellRef`" t=`"inlineStr`"><is><t>$(Escape-Xml $value)</t></is></c>")
        }

        $sheetRows.Add("<row r=`"$rowNumber`">$($cells -join '')</row>")
    }

    $sheetXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>$($sheetRows -join '')</sheetData>
</worksheet>
"@

    $workbookXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Importar" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
"@

    $workbookRelsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
"@

    $relsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
"@

    $contentTypesXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
"@

    $appXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>PI1 UNIVESP</Application>
</Properties>
"@

    $created = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
    $coreXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:dcterms="http://purl.org/dc/terms/"
 xmlns:dcmitype="http://purl.org/dc/dcmitype/"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>Planilha de Importação</dc:title>
  <dc:creator>Cursor Agent</dc:creator>
  <cp:lastModifiedBy>Cursor Agent</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">$created</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">$created</dcterms:modified>
</cp:coreProperties>
"@

    $tempDir = Join-Path ([System.IO.Path]::GetTempPath()) ("xlsx_" + [guid]::NewGuid().ToString("N"))
    New-Item -ItemType Directory -Path $tempDir | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $tempDir "_rels") | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $tempDir "docProps") | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $tempDir "xl") | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $tempDir "xl\_rels") | Out-Null
    New-Item -ItemType Directory -Path (Join-Path $tempDir "xl\worksheets") | Out-Null

    Set-Content -LiteralPath (Join-Path $tempDir "[Content_Types].xml") -Value $contentTypesXml
    Set-Content -LiteralPath (Join-Path $tempDir "_rels\.rels") -Value $relsXml
    Set-Content -LiteralPath (Join-Path $tempDir "docProps\app.xml") -Value $appXml
    Set-Content -LiteralPath (Join-Path $tempDir "docProps\core.xml") -Value $coreXml
    Set-Content -LiteralPath (Join-Path $tempDir "xl\workbook.xml") -Value $workbookXml
    Set-Content -LiteralPath (Join-Path $tempDir "xl\_rels\workbook.xml.rels") -Value $workbookRelsXml
    Set-Content -LiteralPath (Join-Path $tempDir "xl\worksheets\sheet1.xml") -Value $sheetXml

    $zipPath = [System.IO.Path]::ChangeExtension($OutputPath, ".zip")
    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
    if (Test-Path $OutputPath) { Remove-Item $OutputPath -Force }

    Compress-Archive -Path (Join-Path $tempDir "*") -DestinationPath $zipPath -Force
    Rename-Item -Path $zipPath -NewName ([System.IO.Path]::GetFileName($OutputPath))
    Move-Item -Path (Join-Path (Split-Path $zipPath -Parent) ([System.IO.Path]::GetFileName($OutputPath))) -Destination $OutputPath -Force

    Remove-Item $tempDir -Recurse -Force
}

$baseDir = Resolve-Path (Join-Path $PSScriptRoot "..")
$outputDir = Join-Path $baseDir "planilhas_importacao_teste"
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

$files = @(
    @{
        Name = "01_categorias_peca_teste.xlsx"
        Header = @("nome", "descricao")
        Rows = @(
            @("Freio", "Componentes de frenagem"),
            @("Suspensão", "Componentes de suspensão e estabilidade"),
            @("Ignição", "Componentes de ignição do motor"),
            @("Filtros", "Filtros de óleo, ar e combustível"),
            @("Elétrica", "Componentes elétricos automotivos")
        )
    },
    @{
        Name = "02_tipos_peca_teste.xlsx"
        Header = @("categoria_nome", "nome", "descricao")
        Rows = @(
            @("Freio", "Pastilha de freio dianteira", "Pastilha para eixo dianteiro"),
            @("Suspensão", "Amortecedor dianteiro", "Amortecedor hidráulico dianteiro"),
            @("Suspensão", "Bieleta", "Bieleta da barra estabilizadora"),
            @("Ignição", "Vela de ignição", "Vela de ignição de motor flex"),
            @("Filtros", "Filtro de óleo", "Filtro de óleo do motor")
        )
    },
    @{
        Name = "03_marcas_produto_teste.xlsx"
        Header = @("nome")
        Rows = @(
            @("Bosch"),
            @("Cofap"),
            @("Nakata"),
            @("Fras-le"),
            @("Magneti Marelli")
        )
    },
    @{
        Name = "04_produtos_teste.xlsx"
        Header = @("tipo_peca_nome", "marca_produto_nome", "sku_interno", "codigo_fabricante", "codigo_barras", "nome_comercial", "descricao", "custo", "preco", "estoque_minimo")
        Rows = @(
            @("Pastilha de freio dianteira", "Bosch", "SKU-000001", "BOS-PFD-ONIX-20", "7891000000001", "Pastilha de freio dianteira Bosch Onix 1.0 2020", "Jogo de pastilhas dianteiras para uso urbano", "85.00", "129.90", "4"),
            @("Amortecedor dianteiro", "Cofap", "SKU-000002", "COF-AMDT-HB20-21", "7891000000002", "Amortecedor dianteiro Cofap HB20 1.0 2021", "Amortecedor dianteiro pressurizado", "210.00", "319.90", "2"),
            @("Bieleta", "Nakata", "SKU-000003", "NAK-BIEL-ARGO-22", "7891000000003", "Bieleta Nakata Argo 1.0 2022", "Bieleta dianteira para barra estabilizadora", "42.00", "69.90", "6"),
            @("Vela de ignição", "Bosch", "SKU-000004", "BOS-VELA-GOL-18", "7891000000004", "Vela de ignição Bosch Gol 1.6 2018", "Vela de ignição para motor flex 1.6", "24.00", "39.90", "8"),
            @("Filtro de óleo", "Magneti Marelli", "SKU-000005", "MARE-FOL-COR-20", "7891000000005", "Filtro de óleo Magneti Marelli Corolla 2.0 2020", "Filtro de óleo blindado de alta vazão", "18.00", "29.90", "10")
        )
    },
    @{
        Name = "05_marcas_veiculo_teste.xlsx"
        Header = @("nome")
        Rows = @(
            @("Chevrolet"),
            @("Hyundai"),
            @("Fiat"),
            @("Volkswagen"),
            @("Toyota")
        )
    },
    @{
        Name = "06_modelos_veiculo_teste.xlsx"
        Header = @("marca_nome", "nome")
        Rows = @(
            @("Chevrolet", "Onix"),
            @("Hyundai", "HB20"),
            @("Fiat", "Argo"),
            @("Volkswagen", "Gol"),
            @("Toyota", "Corolla")
        )
    },
    @{
        Name = "07_configuracoes_veiculo_teste.xlsx"
        Header = @("modelo_veiculo_nome", "ano_inicio", "ano_fim", "motorizacao", "combustivel", "versao", "observacoes")
        Rows = @(
            @("Chevrolet / Onix", "2020", "2020", "1.0", "Flex", "LT", "Configuração de demonstração"),
            @("Hyundai / HB20", "2021", "2021", "1.0", "Flex", "Vision", "Configuração de demonstração"),
            @("Fiat / Argo", "2022", "2022", "1.0", "Flex", "Drive", "Configuração de demonstração"),
            @("Volkswagen / Gol", "2018", "2018", "1.6", "Flex", "Trendline", "Configuração de demonstração"),
            @("Toyota / Corolla", "2020", "2020", "2.0", "Flex", "XEi", "Configuração de demonstração")
        )
    },
    @{
        Name = "08_estoques_teste.xlsx"
        Header = @("nome", "localizacao")
        Rows = @(
            @("Estoque Principal A1", "Corredor A / Prateleira 01"),
            @("Estoque Principal A2", "Corredor A / Prateleira 02"),
            @("Estoque Principal B1", "Corredor B / Prateleira 01"),
            @("Estoque Secundário P3", "Prateleira 03"),
            @("Balcão Peças Rápidas", "Gaveta Peças Rápidas")
        )
    }
)

foreach ($f in $files) {
    $target = Join-Path $outputDir $f.Name
    New-XlsxFile -OutputPath $target -Header $f.Header -Rows $f.Rows
}

Write-Output "Planilhas geradas em: $outputDir"
