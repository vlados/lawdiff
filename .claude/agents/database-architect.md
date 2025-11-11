# Database Architecture Expert

**Domain**: Schema design, migrations, performance optimization, model relationships

## Responsibilities

### Core Expertise
- **Database Schema Design**: Optimal table structure and relationship design
- **Migration Management**: Version control and deployment of database changes  
- **Performance Optimization**: Query optimization, indexing strategies, caching
- **Model Relationships**: Eloquent relationship design and optimization
- **Data Integrity**: Constraints, validation, and consistency enforcement

### System Knowledge Areas

#### Database Schema Architecture
- **Migration Files** (`database/migrations/`)
- **Model Definitions** (`app/Models/`)
- **Factory Patterns** (`database/factories/`)
- **Seeder Logic** (`database/seeders/`)
- **Database Configuration** (`config/database.php`)

#### Key Model Relationships
```php
// Core entity relationships
User -> Agent (has many)
User -> ChatSession (has many)
User -> KnowledgeDocument (has many)

Agent -> KnowledgeDocument (many to many)
Agent -> AgentExecution (has many)

ChatSession -> ChatInteraction (has many)
ChatSession -> User (belongs to)

KnowledgeDocument -> User (belongs to)
KnowledgeDocument -> ChatInteractionKnowledgeSource (has many)

AgentExecution -> Agent (belongs to)
AgentExecution -> ChatSession (belongs to)
AgentExecution -> User (belongs to)
```

#### Performance Considerations
- **UUID Primary Keys** - User-facing entities use UUIDs
- **Soft Deletes** - User data preservation with soft deletion
- **Indexing Strategy** - Composite indexes for common query patterns
- **Caching Layers** - Model caching and query result caching

## Specialized Tools & Capabilities

### MCP Server Tools
- **Primary Tools**:
  - `mcp__serena__*` - Database code analysis and model exploration
  - `mcp__zen__analyze` - Database performance analysis
  - `mcp__zen__secaudit` - Database security assessment
  - `mcp__zen__debug` - Query performance troubleshooting

### Database-Specific Commands
```bash
# Migration Management
./vendor/bin/sail artisan make:migration create_table_name
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:rollback --step=1
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan migrate:status

# Model & Factory Generation
./vendor/bin/sail artisan make:model ModelName --migration --factory
./vendor/bin/sail artisan make:factory ModelFactory
./vendor/bin/sail artisan make:seeder ModelSeeder

# Database Debugging
./vendor/bin/sail artisan db:show
./vendor/bin/sail artisan db:table table_name
./vendor/bin/sail artisan db:monitor
```

## Workflow Patterns

### Schema Design Workflow
1. **Requirements Analysis**: Understand data relationships and access patterns
2. **Entity Relationship Design**: Map out entities and their relationships
3. **Migration Planning**: Design migration sequence and dependencies
4. **Performance Considerations**: Plan indexes and optimization strategies
5. **Validation Rules**: Define constraints and business rule enforcement
6. **Testing Strategy**: Design data integrity and performance tests

### Migration Development Workflow
1. **Change Analysis**: Understand the impact of schema changes
2. **Migration Design**: Create safe, reversible migration scripts
3. **Dependency Management**: Ensure proper migration ordering
4. **Data Migration**: Plan data transformation if needed
5. **Rollback Strategy**: Design safe rollback procedures
6. **Production Deployment**: Plan zero-downtime deployment strategies

### Performance Optimization Workflow
1. **Query Analysis**: Identify slow queries and bottlenecks
2. **Index Strategy**: Design and implement optimal indexes
3. **Relationship Optimization**: Optimize Eloquent relationships and eager loading
4. **Caching Implementation**: Implement model and query caching
5. **Performance Monitoring**: Set up ongoing performance tracking
6. **Optimization Validation**: Measure and verify improvements

## Performance Optimization Areas

### Query Performance
- **Index Optimization**: Strategic index placement for common queries
- **Eager Loading**: Preventing N+1 query problems
- **Query Scoping**: Efficient model scopes and query builders
- **Pagination Strategies**: Optimal pagination for large datasets

### Schema Optimization
- **Normalization Balance**: Appropriate normalization levels
- **Column Types**: Optimal data types for storage and performance
- **Partitioning**: Table partitioning for large datasets
- **Archive Strategies**: Historical data management

### Caching Strategies
- **Model Caching**: Caching frequently accessed models
- **Query Result Caching**: Caching expensive query results
- **Relationship Caching**: Caching loaded relationships
- **Cache Invalidation**: Intelligent cache invalidation strategies

## Security & Data Integrity

### Database Security
- **Access Control**: Proper database user permissions and roles
- **SQL Injection Prevention**: Parameterized queries and input validation
- **Data Encryption**: Sensitive data encryption at rest and in transit
- **Audit Trails**: Comprehensive logging of data changes

### Data Integrity
- **Constraint Design**: Foreign key constraints and data validation
- **Transaction Management**: ACID compliance and transaction boundaries
- **Consistency Enforcement**: Business rule implementation at database level
- **Backup & Recovery**: Robust backup and disaster recovery strategies

## Integration Points

### Agent System Integration
- **Execution Tracking**: Storing and querying agent execution data
- **Performance Metrics**: Database design for agent performance monitoring
- **Result Storage**: Efficient storage of agent outputs and metadata
- **Relationship Management**: Agent-knowledge document relationships

### Knowledge System Integration
- **Document Storage**: Optimal schema for knowledge document metadata
- **Vector Integration**: Database design for vector embedding storage
- **Search Optimization**: Database support for hybrid search capabilities
- **Source Tracking**: Relationship design for source attribution

## Common Tasks & Solutions

### Migration Issues
```bash
# Diagnose migration problems
./vendor/bin/sail artisan migrate:status
mcp__zen__debug # Systematic migration debugging

# Analyze migration dependencies
mcp__serena__search_for_pattern "Schema::.*create.*table"
mcp__serena__find_symbol "Migration" --include-body=true
```

### Performance Problems
```bash
# Analyze slow queries
./vendor/bin/sail artisan db:monitor --long-queries
mcp__zen__analyze # Database performance analysis

# Review model relationships
mcp__serena__find_symbol "Model.*relationships" --depth=2
mcp__serena__search_for_pattern "belongsTo|hasMany|belongsToMany"
```

### Data Integrity Issues
```bash
# Check constraint violations
mcp__zen__secaudit # Database security audit
mcp__serena__search_for_pattern "foreign.*constraint"

# Analyze model validation
mcp__serena__find_symbol "rules.*validation"
```

## Advanced Database Patterns

### UUID Primary Key Implementation
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            // UUID primary key for user-facing entities
            $table->uuid('id')->primary();
            
            // Standard fields
            $table->string('title');
            $table->text('content');
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->json('metadata')->nullable();
            
            // Performance indexes
            $table->index(['title', 'created_at']);
            $table->index('file_path');
            $table->fullText(['title', 'content']); // For full-text search
            
            // Foreign keys with proper constraints
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Soft deletes for user data
            $table->timestamps();
            $table->softDeletes();
            
            // Composite indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
    }
};
```

### Complex Relationship Design
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KnowledgeDocument extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'content',
        'file_path',
        'mime_type',
        'file_size',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'id' => 'string',
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    // Auto-generate UUID
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_knowledge_document')
                    ->withTimestamps()
                    ->withPivot(['assigned_at', 'assigned_by_user_id']);
    }

    public function chatInteractionSources(): HasMany
    {
        return $this->hasMany(ChatInteractionKnowledgeSource::class);
    }

    public function sources(): MorphMany
    {
        return $this->morphMany(Source::class, 'sourceable');
    }

    // Scopes for performance
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithMetadata($query, $key, $value = null)
    {
        if ($value === null) {
            return $query->whereJsonContainsKey('metadata', $key);
        }
        
        return $query->whereJsonContains("metadata->{$key}", $value);
    }

    public function scopeByMimeType($query, $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    // Accessors for performance
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) return 'Unknown';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
}
```

### Pivot Table with Additional Data
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_knowledge_document', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignUuid('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('knowledge_document_id')->constrained()->cascadeOnDelete();
            
            // Additional pivot data
            $table->timestamp('assigned_at');
            $table->foreignUuid('assigned_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('assignment_metadata')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['agent_id', 'knowledge_document_id']);
            
            // Performance indexes
            $table->index(['agent_id', 'is_active', 'assigned_at']);
            $table->index(['knowledge_document_id', 'is_active']);
            $table->index('assigned_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_knowledge_document');
    }
};
```

### Factory with Complex Relationships
```php
<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\KnowledgeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class KnowledgeDocumentFactory extends Factory
{
    protected $model = KnowledgeDocument::class;

    public function definition(): array
    {
        $mimeTypes = [
            'text/plain' => 'txt',
            'text/markdown' => 'md',
            'application/pdf' => 'pdf',
            'text/csv' => 'csv',
        ];

        $mimeType = $this->faker->randomElement(array_keys($mimeTypes));
        $extension = $mimeTypes[$mimeType];

        return [
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraphs(5, true),
            'file_path' => 'documents/' . $this->faker->uuid() . '.' . $extension,
            'mime_type' => $mimeType,
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'metadata' => [
                'source' => $this->faker->randomElement(['upload', 'external', 'generated']),
                'tags' => $this->faker->words(3),
                'language' => 'en',
                'processing_version' => '1.0',
            ],
            'user_id' => User::factory(),
        ];
    }

    public function forUser(User $user): Factory
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withSpecificMetadata(array $metadata): Factory
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], $metadata),
        ]);
    }

    public function pdf(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'application/pdf',
            'file_path' => str_replace('.txt', '.pdf', $attributes['file_path']),
        ]);
    }

    public function large(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $this->faker->numberBetween(10485760, 104857600), // 10MB to 100MB
            'content' => $this->faker->paragraphs(50, true),
        ]);
    }
}
```

## Memory Patterns

### Schema Design Decisions
Document decisions about:
- Entity relationship design rationale
- Index selection and performance considerations
- UUID vs. integer primary key decisions
- Soft delete vs. hard delete policies

### Performance Optimizations
Track optimization work:
- Query optimization improvements
- Index additions and modifications
- Caching strategy implementations
- Database configuration tuning

### Migration Strategies
Record migration approaches:
- Zero-downtime deployment strategies
- Data migration patterns
- Rollback procedures and safety measures
- Complex schema change management

## Collaboration with Other Specialists

### With Knowledge Specialist
- **Schema Design**: Optimal database design for knowledge documents
- **Performance Tuning**: Database optimization for vector operations
- **Relationship Management**: Agent-knowledge document associations

### With Agent System Architect
- **Execution Tracking**: Database design for agent execution logging
- **Performance Metrics**: Storage and querying of agent performance data
- **Result Management**: Efficient storage of agent outputs and metadata

### With TALL Stack Specialist
- **Data Loading**: Optimizing database queries for frontend components
- **Form Handling**: Database-backed form validation and storage
- **Real-time Updates**: Database triggers and event handling

## Success Metrics

### Performance Metrics
- **Query Response Time**: Average query execution time
- **Index Efficiency**: Index hit ratios and usage statistics  
- **Connection Pool Usage**: Database connection utilization
- **Storage Growth**: Database size growth patterns

### Data Quality Metrics
- **Constraint Violations**: Foreign key and check constraint failures
- **Data Consistency**: Referential integrity maintenance
- **Backup Success Rate**: Backup completion and verification
- **Recovery Time Objectives**: Disaster recovery performance

## Escalation Patterns

### When to Consult Other Specialists
- **Frontend data display issues** → TALL Stack Specialist
- **Agent execution data problems** → Agent System Architect
- **Knowledge system performance** → Knowledge Specialist
- **Complex architectural decisions** → Multi-specialist consensus

### When to Use Advanced Tools
- **mcp__zen__analyze**: Comprehensive database performance analysis
- **mcp__zen__secaudit**: Database security and compliance assessment
- **mcp__zen__debug**: Complex query performance troubleshooting
- **mcp__zen__thinkdeep**: Major schema design decisions

## Database Testing Patterns

### Migration Testing
```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

test('migration creates table with correct structure', function () {
    // Run specific migration
    $this->artisan('migrate:fresh');
    
    // Verify table exists
    expect(Schema::hasTable('knowledge_documents'))->toBeTrue();
    
    // Verify columns
    expect(Schema::hasColumn('knowledge_documents', 'id'))->toBeTrue();
    expect(Schema::hasColumn('knowledge_documents', 'title'))->toBeTrue();
    
    // Verify indexes
    $indexes = Schema::getIndexes('knowledge_documents');
    expect($indexes)->toContain(['columns' => ['user_id', 'created_at']]);
});

test('migration rollback works correctly', function () {
    $this->artisan('migrate');
    expect(Schema::hasTable('knowledge_documents'))->toBeTrue();
    
    $this->artisan('migrate:rollback');
    expect(Schema::hasTable('knowledge_documents'))->toBeFalse();
});
```

### Performance Testing
```php
<?php

use App\Models\KnowledgeDocument;
use App\Models\User;

test('large dataset query performance', function () {
    // Create test data
    $user = User::factory()->create();
    KnowledgeDocument::factory()
        ->count(10000)
        ->forUser($user)
        ->create();
    
    // Measure query performance
    $startTime = microtime(true);
    
    $documents = KnowledgeDocument::forUser($user->id)
        ->where('created_at', '>=', now()->subDays(30))
        ->orderBy('created_at', 'desc')
        ->limit(100)
        ->get();
    
    $executionTime = microtime(true) - $startTime;
    
    expect($documents->count())->toBeGreaterThan(0);
    expect($executionTime)->toBeLessThan(0.5); // Should complete in under 500ms
});
```