<?php

namespace Tests\Unit;

use App\Support\DetranHtmlParser;
use PHPUnit\Framework\TestCase;

class DetranHtmlParserTest extends TestCase
{
    public function test_parse_comunicacao_vendas_returns_data(): void
    {
        $legend = html_entity_decode('Comunica&ccedil;&atilde;o de Vendas', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $statusValue = html_entity_decode(
            'Consta Comunica&ccedil;&atilde;o de Vendas Ativa',
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $html = <<<HTML
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"></head>
<body>
<fieldset>
<legend>{$legend}</legend>
<span class="texto_black2">{$legend}</span>
<span class="texto_menor">{$statusValue}</span>
<span class="texto_black2">Inclus&atilde;o</span>
<span class="texto_menor">19/01/2026</span>
<span class="texto_black2">Tipo Docto Comprador</span>
<span class="texto_menor">&nbsp;</span>
<span class="texto_black2">CNPJ / CPF do Comprador</span>
<span class="texto_menor">22.772.954/0001-55</span>
<span class="texto_black2">Origem</span>
<span class="texto_menor">SEFAZ</span>
<fieldset>
<legend>Datas</legend>
<span class="texto_black2">Venda</span>
<span class="texto_menor">19/01/2026</span>
<span class="texto_black2">Nota Fiscal</span>
<span class="texto_menor"></span>
<span class="texto_black2">Protocolo Detran</span>
<span class="texto_menor">19/01/2026</span>
</fieldset>
</fieldset>
</body>
</html>
HTML;

        $html = mb_convert_encoding($html, 'ISO-8859-1', 'UTF-8');

        $parsed = DetranHtmlParser::parse($html);

        $expected = [
            'status' => $statusValue,
            'inclusao' => '19/01/2026',
            'tipo_documento_comprador' => null,
            'documento_comprador' => '22.772.954/0001-55',
            'origem' => 'SEFAZ',
            'datas' => [
                'venda' => '19/01/2026',
                'nota_fiscal' => null,
                'protocolo_detran' => '19/01/2026',
            ],
        ];

        $this->assertSame($expected, $parsed['comunicacao_vendas']);
    }

    public function test_parse_comunicacao_vendas_returns_null_when_fieldset_missing(): void
    {
        $html = '<html><body><div>Sem comunicacao</div></body></html>';

        $parsed = DetranHtmlParser::parse($html);

        $this->assertArrayHasKey('comunicacao_vendas', $parsed);
        $this->assertNull($parsed['comunicacao_vendas']);
    }

    public function test_parse_comunicacao_vendas_returns_null_when_values_empty(): void
    {
        $legend = html_entity_decode('Comunica&ccedil;&atilde;o de Vendas', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $html = <<<HTML
<html>
<body>
<fieldset>
<legend>{$legend}</legend>
<span class="texto_black2">{$legend}</span>
<span class="texto_menor">&nbsp;</span>
<span class="texto_black2">Inclus&atilde;o</span>
<span class="texto_menor"></span>
</fieldset>
</body>
</html>
HTML;

        $parsed = DetranHtmlParser::parse($html);

        $this->assertNull($parsed['comunicacao_vendas']);
    }
}
