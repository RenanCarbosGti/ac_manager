<?php
// model/equipamento.php

class equipamento
{
    private $idequipamento;
    private $codigo_qr;
    private $idcliente;
    private $nome_cliente;
    private $endereco;
    private $telefone;
    private $modelo;
    private $marca;
    private $observacao;

    public function __construct($idequipamento, $codigo_qr, $nome_cliente, $endereco, $telefone, $modelo, $marca, $observacao = '', $idcliente = null)
    {
        $this->idequipamento = $idequipamento;
        $this->codigo_qr     = $codigo_qr;
        $this->idcliente     = $idcliente;
        $this->nome_cliente  = $nome_cliente;
        $this->endereco      = $endereco;
        $this->telefone      = $telefone;
        $this->modelo        = $modelo;
        $this->marca         = $marca;
        $this->observacao    = $observacao;
    }

    public function __get($key)  { return $this->{$key}; }
    public function __set($key, $value) { $this->{$key} = $value; }
}
