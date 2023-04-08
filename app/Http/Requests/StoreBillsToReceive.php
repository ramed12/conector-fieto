<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillsToReceive extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'E1_FILIAL'                      => 'required',
            'E1_PREFIXO'                     => 'required',
            'E1_NUM'                         => 'required',
            //'E1_PARCELA'                     => 'required',
            'E1_CLIENTE'                     => 'required',
            'E1_LOJA'                        => 'required',
            'E1_NOMCLI'                      => 'required',
            'E1_EMISSAO'                     => 'required',
            'E1_VENCREA'                     => 'required',
            'E1_VALOR'                       => 'required',
            'E1_BAIXA'                       => 'required',
            //'E1_MDCONTR'                     => 'required',
            //'E1_MEDNUME'                     => 'required',
            'BAIXASCONTASRECEBER.*.E5_FILIAL'  => 'required',
            'BAIXASCONTASRECEBER.*.E5_NUMERO'  => 'required',
            //'BAIXASCONTASRECEBER.*.E5_PARCELA' => 'required',
            'BAIXASCONTASRECEBER.*.E5_CLIFOR'  => 'required',
            'BAIXASCONTASRECEBER.*.E5_DATA'    => 'required',
            'BAIXASCONTASRECEBER.*.E5_VLJUROS' => 'required',
            'BAIXASCONTASRECEBER.*.E5_VLMULTA' => 'required',
            'BAIXASCONTASRECEBER.*.E5_VLDESCO' => 'required',
            'BAIXASCONTASRECEBER.*.E5_DTDISPO' => 'required',
        ];
    }

    /**
    * Get custom attributes for validator errors.
    *
    * @return array
    */
    public function attributes()
    {
        $attibutes = [
            'E1_FILIAL'  => 'filial',
            'E1_PREFIXO' => 'prefixo',
            'E1_NUM'     => 'numero',
            'E1_PARCELA' => 'parcela',
            'E1_CLIENTE' => 'cliente',
            'E1_LOJA'    => 'loja',
            'E1_NOMCLI'  => 'nome cliente',
            'E1_EMISSAO' => 'emissão',
            'E1_VENCREA' => 'vencimento',
            'E1_VALOR'   => 'valor',
            'E1_BAIXA'   => 'baixa',
            'E1_MDCONTR' => 'medição contrato',
            'E1_MEDNUME' => 'numero da medição',
            'BAIXASCONTASRECEBER.*.E5_FILIAL'  => 'filial',
            'BAIXASCONTASRECEBER.*.E5_NUMERO'  => 'numero',
            'BAIXASCONTASRECEBER.*.E5_PARCELA' => 'parcela',
            'BAIXASCONTASRECEBER.*.E5_CLIFOR'  => 'cliente fornecedor',
            'BAIXASCONTASRECEBER.*.E5_DATA'    => 'data',
            'BAIXASCONTASRECEBER.*.E5_VLJUROS' => 'valor juros',
            'BAIXASCONTASRECEBER.*.E5_VLMULTA' => 'valor multa',
            'BAIXASCONTASRECEBER.*.E5_VLDESCO' => 'valor desconto',
            'BAIXASCONTASRECEBER.*.E5_DTDISPO' => 'Data Dispo'
        ];

        return $attibutes;
    }
}
