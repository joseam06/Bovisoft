<?php

namespace App\Services;

use App\Models\{Egreso, Ingreso, Salud, User};

class OfflineRemainingViews
{
    public function render($farm,$animal,User $user): array
    {
        $result=[];
        foreach(['ingresos'=>Ingreso::class,'egresos'=>Egreso::class] as $module=>$class){
            $record=(new $class)->forceFill(['id'=>0,'user_id'=>$user->id,'finca_id'=>0,'animal_id'=>0,'codigo'=>'Código','tipo'=>'otro','categoria'=>'otro',
                'fecha'=>today(),'monto'=>'1.00','descripcion'=>'Descripción','observaciones'=>'Observaciones','comprador_nombre'=>'Comprador',
                'comprador_telefono'=>'Teléfono','comprador_documento'=>'Documento','version'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $record->setRelation('finca',$farm)->setRelation('animal',$animal);
            if($module==='egresos')$record->setRelation('salud',null);
            foreach(['index','show','edit','create','index-empty','create-empty'] as $kind){
                $view=$kind==='show'?'show':'create';
                $result[$module][$kind]=view('finanzas.'.$module.'.'.$view,[$module==='ingresos'?'ingreso':'egreso'=>$record,
                    'fincas'=>collect([$farm]),'animales'=>collect([$animal]),'tipos'=>Ingreso::getTipos(),'categorias'=>Egreso::getCategorias(),
                    'codigo'=>'Código al sincronizar','animalPresel'=>null,'saludPresel'=>null,'tipoPresel'=>'otro','offlineTemplate'=>true])->render();
            }
            $samples[$module]=$record;
        }
        $result['finanzas']['index']=view('finanzas.index',['totalIngresos'=>'0.00','totalEgresos'=>'0.00','balance'=>'0.00','costosSalud'=>'0.00',
            'ingresosPorTipo'=>collect(['otro'=>'1.00']),'egresosPorCategoria'=>collect(['otro'=>'1.00']), 'ultimosIngresos'=>collect([$samples['ingresos']]),
            'ultimosEgresos'=>collect([$samples['egresos']]),'flujoCaja'=>[],'balancePorFinca'=>[['finca'=>'Nombre','ingresos'=>1,'egresos'=>1,'balance'=>0]],
            'fincas'=>collect([$farm]),'fincaId'=>null,'periodo'=>'mes','fechaInicio'=>today()->startOfMonth(),'fechaFin'=>today()->endOfMonth(),'offlineTemplate'=>true])->render();
        foreach(['index','show','edit','create','index-empty','create-empty'] as $kind)$result['perfil'][$kind]=view('profile.edit',['user'=>$user,'offlineTemplate'=>true])->render();
        $alerts=[];foreach(['critico','urgente','advertencia','info'] as $level)$alerts[]=['id'=>'sample-'.$level,'tipo'=>'proxima','nivel'=>$level,'titulo'=>'Título','descripcion'=>'Descripción','animal'=>'Animal','finca'=>'Finca','fecha'=>today()->format('d/m/Y'),'salud_id'=>0,'categoria'=>'preventivo','icono'=>'fa-bell','color'=>'blue'];
        foreach(['index','index-empty'] as $kind){$all=collect($kind==='index'?$alerts:[]);$result['alertas'][$kind]=view('alertas.index',[
            'alertas'=>$all,'criticas'=>$all->where('nivel','critico'),'urgentes'=>$all->where('nivel','urgente'),'advertencias'=>$all->where('nivel','advertencia'),'informativas'=>$all->where('nivel','info'),'offlineTemplate'=>true])->render();}
        $result['reportes']['index']=view('reportes.index',['fincas'=>collect([$farm]),'animales'=>collect([$animal]),'totalAnimales'=>0,'totalSalud'=>0,'totalIngresos'=>'0.00','totalEgresos'=>'0.00','offlineTemplate'=>true])->render();
        $health=(new Salud)->forceFill(['id'=>0,'codigo'=>'Código','user_id'=>$user->id,'finca_id'=>0,'animal_id'=>0,'tipo'=>'tratamiento','categoria'=>'clinico','estado'=>'en_tratamiento',
            'fecha_aplicacion'=>today(),'proxima_aplicacion'=>today()->addDays(3),'fin_carencia'=>today()->addDays(3),'dias_carencia'=>3,'costo'=>'1.00','dosis'=>'1.00','nombre_producto'=>'Producto','veterinario'=>'Veterinario','diagnostico'=>'Diagnóstico','observaciones'=>'Observaciones']);
        $health->setRelation('animal',$animal)->setRelation('finca',$farm);
        $base=['titulo'=>'Reporte','fincaNombre'=>'Todas las fincas','propietario'=>$user->name,'usuarioNombre'=>$user->name,'periodo'=>'Período','generadoEl'=>now()->format('d/m/Y H:i'),
            'animal'=>$animal,'animales'=>collect([$animal]),'registros'=>collect([$health]),'historial'=>collect([$health]),'activos'=>collect([$health]),'conteoTipos'=>collect(['vaca'=>1]),
            'ingresos'=>collect([$samples['ingresos']]),'egresos'=>collect([$samples['egresos']]),'enCarencia'=>collect([$health]),'registrosTratamientos'=>collect([$health]),
            'alertasVencidas'=>collect([$health]),'alertasProximas'=>collect([$health]),'proximasDosis'=>collect([$health]),'pesoPromedio'=>1,'maxMonto'=>1];
        foreach(['totalAnimales','vacasActivas','vendidos','totalRegistros','vacunaciones','desparasitaciones','costoTotal','enCarenciaCount','tratamientosActivos','totalVacunaciones','totalDesparasitaciones','proximasSiete','totalActivos','totalCompletados','tratamientos','totalIngresos','totalEgresos','balance','costosSalud'] as $name)$base[$name]=1;
        foreach(['inventario'=>'inventario','salud'=>'salud','vacunacion'=>'salud_vacunacion','tratamientos'=>'salud_tratamientos','historial'=>'salud_historial_animal','financiero'=>'financiero'] as $kind=>$view){
            $vars=$base;if($kind==='inventario')$vars['enCarencia']=1;
            $result['reportes']['print_'.$kind]=view('reportes.pdf.'.$view,$vars)->render();
        }
        return $result;
    }
}
