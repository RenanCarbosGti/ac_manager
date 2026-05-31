<?php
// model/financeiro.php

class financeiro
{
    private $idfinanceiro;
    private $tipo;
    private $descricao;
    private $valor;
    private $data_lancamento;
    private $idordem;
    private $categoria;
    private $observacoes;

    public function __construct($idfinanceiro, $tipo, $descricao, $valor, $data_lancamento, $idordem, $categoria, $observacoes)
    {
        $this->idfinanceiro     = $idfinanceiro;
        $this->tipo             = $tipo;
        $this->descricao        = $descricao;
        $this->valor            = $valor;
        $this->data_lancamento  = $data_lancamento;
        $this->idordem          = $idordem;
        $this->categoria        = $categoria;
        $this->observacoes      = $observacoes;
    }

    public function __get($key) { return $this->{$key}; }
    public function __set($key, $value) { $this->{$key} = $value; }
}
