<?php
namespace HexaGen\Core\Exceptions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ExceptionHandlerInterface
{
    public function report(\Throwable $e): void;

    public function render(Request $request, \Throwable $e): Response;
}
