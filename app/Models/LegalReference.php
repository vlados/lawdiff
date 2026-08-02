<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalReference extends Model
{
    public const STATUS_RESOLVED = 'resolved';

    /**
     * The citation names a provision of this law that no node matches — a
     * renumbered target, or a marker the parser still reads wrongly. Worth
     * surfacing rather than dropping: it is the corpus telling on itself.
     */
    public const STATUS_UNRESOLVED_INTERNAL = 'unresolved_internal';

    /**
     * The citation names another act. The act is recorded as cited; matching it
     * to a law needs a title index that does not exist yet.
     */
    public const STATUS_UNRESOLVED_EXTERNAL = 'unresolved_external';

    public const RELATION_REFERS_TO = 'refers_to';

    protected $fillable = [
        'law_id',
        'source_node_id',
        'target_node_id',
        'target_law_id',
        'target_path',
        'target_act_name',
        'citation_text',
        'relation_type',
        'resolution_status',
        'position',
    ];

    public function law(): BelongsTo
    {
        return $this->belongsTo(Law::class);
    }

    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(LawNode::class, 'source_node_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(LawNode::class, 'target_node_id');
    }

    public function targetLaw(): BelongsTo
    {
        return $this->belongsTo(Law::class, 'target_law_id');
    }
}
