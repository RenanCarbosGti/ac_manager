<?php
// model/cliente.php

class cliente
{
    private $idcliente;
    private $nome;
    private $telefone;
    private $endereco;

    public function __construct($idcliente, $nome, $telefone, $endereco)
    {
        $this->idcliente = $idcliente;
        $this->nome      = $nome;
        $this->telefone  = $telefone;
        $this->endereco  = $endereco;
    }

    public function __get($key) { return $this->{$key}; }
    public function __set($key, $value) { $this->{$key} = $value; }
}
