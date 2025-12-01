<?php
namespace App\Models;

use CodeIgniter\Model;

class BaseModel extends Model
{
    protected $useTimestamps = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
}
