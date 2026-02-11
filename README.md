# TheDevBacklog

The Backlog (Agent-Native, Agile-Compatible): An A.I. agent framework to run through Tasks and execute them from a backlog.

## About

This Laravel 11 application provides a structured backlog system designed for agent-native task management. It implements a hierarchical structure: **Epics → Stories → Tasks → Runs**, enabling organized task tracking and execution planning.

## Features

- **Hierarchical Backlog Structure**
  - Epics: High-level initiatives
  - Stories: User stories or features within epics
  - Tasks: Specific work items within stories
  - Runs: Execution attempts of tasks

- **Status Tracking**: All entities include status fields for progress monitoring
- **Relationships**: Fully defined Eloquent relationships for easy data navigation
- **RESTful API**: Simple API endpoint to retrieve the complete backlog hierarchy
- **Seeded Data**: Sample data for immediate testing and development

## Database Structure

### Epics
- `id`: Primary key
- `title`: Epic name
- `description`: Detailed description
- `status`: Current state (e.g., pending, in_progress, completed)
- `timestamps`: Created/updated timestamps

### Stories
- `id`: Primary key
- `epic_id`: Foreign key to Epics
- `title`: Story name
- `description`: Detailed description
- `status`: Current state
- `timestamps`: Created/updated timestamps

### Tasks
- `id`: Primary key
- `story_id`: Foreign key to Stories
- `title`: Task name
- `description`: Detailed description
- `status`: Current state
- `timestamps`: Created/updated timestamps

### Runs
- `id`: Primary key
- `task_id`: Foreign key to Tasks
- `status`: Execution state (e.g., pending, in_progress, completed, failed)
- `started_at`: Run start timestamp
- `completed_at`: Run completion timestamp
- `timestamps`: Created/updated timestamps

## Installation

1. Clone the repository:
```bash
git clone https://github.com/CraigThomasParsons/TheDevBacklog.git
cd TheDevBacklog
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations and seeders:
```bash
php artisan migrate:fresh --seed
```

## Usage

### Starting the Application

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### API Endpoints

#### Get Complete Backlog Hierarchy

**Endpoint:** `GET /api/backlog`

**Response:** JSON containing all epics with nested stories, tasks, and runs

```bash
curl http://localhost:8000/api/backlog
```

**Response Structure:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Epic Title",
      "description": "Epic description",
      "status": "in_progress",
      "stories": [
        {
          "id": 1,
          "title": "Story Title",
          "description": "Story description",
          "status": "completed",
          "tasks": [
            {
              "id": 1,
              "title": "Task Title",
              "description": "Task description",
              "status": "completed",
              "runs": [
                {
                  "id": 1,
                  "status": "completed",
                  "started_at": "2026-02-06T18:52:41.000000Z",
                  "completed_at": "2026-02-07T18:52:41.000000Z"
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

## Models and Relationships

### Epic Model
```php
// Relationships
$epic->stories // HasMany relationship to stories
```

### Story Model
```php
// Relationships
$story->epic   // BelongsTo relationship to epic
$story->tasks  // HasMany relationship to tasks
```

### Task Model
```php
// Relationships
$task->story // BelongsTo relationship to story
$task->runs  // HasMany relationship to runs
```

### Run Model
```php
// Relationships
$run->task // BelongsTo relationship to task
```

## Development

### Running Tests
```bash
php artisan test
```

### Database Operations

Reset database with fresh seeded data:
```bash
php artisan migrate:fresh --seed
```

View database structure:
```bash
php artisan db:show --counts
```

List all routes:
```bash
php artisan route:list
```

## Future Development

This is the foundational structure for an agent-native backlog system. Future enhancements will include:

- Execution logic for automated task running
- Agent integration for autonomous task completion
- Advanced filtering and querying capabilities
- Real-time status updates
- Task dependencies and prerequisites
- Progress tracking and reporting
- API endpoints for CRUD operations on all entities

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

