<?php
// model/equipamento.php

class equipamento
{
    private $idequipamento;
    private $codigo_qr;
    private $nome_cliente;
    private $endereco;
    private $telefone;
    private $modelo;
    private $marca;

    public function __construct($idequipamento, $codigo_qr, $nome_cliente, $endereco, $telefone, $modelo, $marca)
    {
        $this->idequipamento = $idequipamento;
        $this->codigo_qr     = $codigo_qr;
        $this->nome_cliente  = $nome_cliente;
        $this->endereco      = $endereco;
        $this->telefone      = $telefone;
        $this->modelo        = $modelo;
        $this->marca         = $marca;
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
