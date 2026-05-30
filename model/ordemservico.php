<?php
// model/ordemservico.php

class ordemservico
{
    private $idordem;
    private $idequipamento;
    private $idservico;
    private $idprofissional;
    private $data_servico;
    private $data_vencimento;
    private $preco_cobrado;
    private $observacoes;
    private $status;

    public function __construct(
        $idordem, $idequipamento, $idservico, $idprofissional,
        $data_servico, $data_vencimento, $preco_cobrado, $observacoes, $status = 'ativo'
    ) {
        $this->idordem          = $idordem;
        $this->idequipamento    = $idequipamento;
        $this->idservico        = $idservico;
        $this->idprofissional   = $idprofissional;
        $this->data_servico     = $data_servico;
        $this->data_vencimento  = $data_vencimento;
        $this->preco_cobrado    = $preco_cobrado;
        $this->observacoes      = $observacoes;
        $this->status           = $status;
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
