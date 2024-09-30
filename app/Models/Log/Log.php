<?php
namespace App\Models\Log;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Log extends BaseModel
{
    use HasFactory;

    /**
     * The types of logs
     */
    const TYPE_INFO = 'INFO';
    const TYPE_ERROR = 'ERROR';

    /**
     * The relation types of logs
     */
    const RELATION_TYPE_TRENDYOL = 'TRENDYOL';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'relation_type',
        'description'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'created_at_text'
    ];

    /**
     * @param string $type
     * @param string $relationType
     * @param string $description
     * @return mixed
     */
    public static function add(string $type, string $relationType, string $description): mixed
    {
        return self::create([
            'type' => $type,
            'relation_type' => $relationType,
            'description' => $description
        ]);
    }

    /**
     * @return string
     */
    protected function getCreatedAtTextAttribute(): string
    {
        return $this->created_at->format('d.m.Y H:i');
    }
}
