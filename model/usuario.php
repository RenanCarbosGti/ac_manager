<?php
// model/usuario.php

class usuario
{
    private $idusuario;
    private $nome;
    private $email;
    private $senha;
    private $tipo;
    private $ativo;

    public function __construct($idusuario, $nome, $email, $senha, $tipo = 'profissional', $ativo = 1)
    {
        $this->idusuario = $idusuario;
        $this->nome      = $nome;
        $this->email     = $email;
        $this->senha     = $senha;
        $this->tipo      = $tipo;
        $this->ativo     = $ativo;
    }

    public function __get($key)
    {
        return $this->{$key};
    }

    public function __set($key, $value)
    {
        $this->{$key} = $value;
    }
}
