<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $class = 'App\\Controllers\\Api\\AuthController';

        $result = [
            'class_exists' => class_exists($class),
        ];

        if (class_exists($class)) {
            $reflection = new \ReflectionClass($class);

            $result['loaded_file'] = $reflection->getFileName();
            $result['methods'] = $reflection->getMethods(
                \ReflectionMethod::IS_PUBLIC
            );

            $methodNames = [];

            foreach ($result['methods'] as $method) {
                $methodNames[] = $method->getName();
            }

            $result['method_names'] = $methodNames;
        }

        return $this->response->setJSON($result);
    }
}