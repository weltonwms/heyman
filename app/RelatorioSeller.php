<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class RelatorioSeller extends Model
{
  
    public $items=[];
    public $total_venda;
    public $total_custo;
    public $totalLucro;

   
    public function getRelatorio()
    {

        $query = \DB::table(function ($q) {
            $q->
                from('produto_venda as pv')
                ->join('produtos as p', 'p.id', '=', 'pv.produto_id')
                ->join('vendas as v', 'v.id', '=', 'pv.venda_id')
                ->join('sellers as s', 's.id', '=', 'v.seller_id')
                ->selectRaw('
                 v.seller_id, s.nome as nome_vendedor,
                SUM(pv.valor_venda*qtd) as total_venda,
                SUM(IF(pv.granel=1,(pv.custo_medio/p.valor_grandeza)*qtd,pv.custo_medio*qtd)) as total_custo
                ');
                if (request('data_venda1')):
                    $dt = request('data_venda1');
                    $q->where('v.data_venda', '>=', $dt);
                endif;
        
                if (request('data_venda2')):
                    $dt = request('data_venda2');
                    $q->where('v.data_venda', '<=', $dt);
                endif;
                if (request('seller_id')):
                    $q->whereIn('v.seller_id', request('seller_id'));
                endif;
                $q
                ->groupByRaw('v.seller_id, s.nome')
                ->get();
        }, 'tab1')->selectRaw('*, ROUND(total_venda - total_custo, 2) as lucro

                 ');

        
        $ordenadoPor = request('ordenado_por', 1);
        $order1 = $ordenadoPor == 1 || $ordenadoPor == 3 ? 'lucro' : 'total_venda';
        $order1 = $ordenadoPor == 2 || $ordenadoPor == 4 ? 'total_venda' : 'lucro';
        $order2 = $ordenadoPor == 1 || $ordenadoPor == 2 ? 'desc' : 'asc';
        $order2 = $ordenadoPor == 3 || $ordenadoPor == 4 ? 'asc' : 'desc';
        $query->orderBy($order1, $order2);
        $this->items = $query->get();
       
        $this->tratarItems($this->items);
        return $this;

    }

    private function tratarItems($items)
    {

        $total_venda = 0;
        $total_custo = 0;
        $totalLucro = 0;

        foreach ($items as $item):
           

            $total_venda += $item->total_venda;
            $total_custo += $item->total_custo;
            $totalLucro += $item->lucro;

        endforeach;

        $this->total_venda = $total_venda;
        $this->total_custo = $total_custo;
        $this->totalLucro = $totalLucro;
    }

    

    
}
