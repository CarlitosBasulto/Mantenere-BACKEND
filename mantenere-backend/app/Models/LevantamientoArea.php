<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevantamientoArea extends Model
{
    use HasFactory;
    protected $fillable = ['negocio_id', 'nombreArea'];
    
    public function equipos()
    {
        return $this->hasMany(LevantamientoEquipo::class);
    }
}
