<?php
// model/profissional.php

class profissional
{
    private $idprofissional;
    private $nome;
    private $telefone;
    private $idusuario;

    public function __construct($idprofissional, $nome, $telefone, $idusuario = null)
    {
        $this->idprofissional = $idprofissional;
        $this->nome           = $nome;
        $this->telefone       = $telefone;
        $this->idusuario      = $idusuario;
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
