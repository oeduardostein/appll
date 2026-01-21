<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\BaseEstadualController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BaseEstadualControllerTest extends TestCase
{
    public function test_has_useful_data_considers_comunicacao_vendas(): void
    {
        $controller = new BaseEstadualController();

        $payload = [
            'comunicacao_vendas' => [
                'status' => 'Consta Comunicacao de Vendas Ativa',
                'inclusao' => null,
                'tipo_documento_comprador' => null,
                'documento_comprador' => null,
                'origem' => null,
                'datas' => [
                    'venda' => null,
                    'nota_fiscal' => null,
                    'protocolo_detran' => null,
                ],
            ],
        ];

        $method = new ReflectionMethod($controller, 'hasUsefulData');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, $payload));
    }
}
