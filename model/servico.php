<?php
// model/servico.php

class servico
{
    private $idservico;
    private $nome;
    private $descricao;
    private $validade_dias;
    private $preco;

    public function __construct($idservico, $nome, $descricao, $validade_dias, $preco)
    {
        $this->idservico     = $idservico;
        $this->nome          = $nome;
        $this->descricao     = $descricao;
        $this->validade_dias = $validade_dias;
        $this->preco         = $preco;
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
