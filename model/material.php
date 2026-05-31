<?php
// model/material.php

class material
{
    private $idmaterial;
    private $nome;
    private $descricao;
    private $unidade;
    private $estoque_atual;
    private $estoque_minimo;
    private $preco_custo;

    public function __construct($idmaterial, $nome, $descricao, $unidade, $estoque_atual, $estoque_minimo, $preco_custo)
    {
        $this->idmaterial    = $idmaterial;
        $this->nome          = $nome;
        $this->descricao     = $descricao;
        $this->unidade       = $unidade;
        $this->estoque_atual = $estoque_atual;
        $this->estoque_minimo = $estoque_minimo;
        $this->preco_custo   = $preco_custo;
    }

    public function __get($key) { return $this->{$key}; }
    public function __set($key, $value) { $this->{$key} = $value; }
}
