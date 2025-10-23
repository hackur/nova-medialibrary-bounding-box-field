# Laravel Nova MediaLibrary Bounding Box Field

A comprehensive Laravel Nova field package that combines [Spatie's MediaLibrary](https://spatie.be/docs/laravel-medialibrary) management with interactive bounding box editing capabilities for visual annotation and damage assessment workflows.

[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)

## Features

### 📦 Dual Functionality
- **MediaLibrary Field** - Complete media upload, management, and cropping via `Medialibrary` field
- **BoundingBox Field** - Interactive canvas-based visual annotation via `BoundingBoxField`

### 🎨 Bounding Box Editor (NEW in v2.0)
- **Interactive Drawing** - Click and drag to create rectangular annotations on images
- **Multi-Box Support** - Add multiple annotations to single images
- **Damage Types** - 15 predefined damage types with color coding and icons
- **Severity Levels** - 3 severity classifications (minor, moderate, severe)
- **Annotation Notes** - Add custom notes to each bounding box
- **Read-Only Mode** - Display annotations without editing capability
- **Addressed Mode** - Toggle between all damage and addressed-only views

### 🖼️ MediaLibrary Management
- Drag and drop file uploads
- Image cropping and editing
- Multiple file support
- Conversion generation
- Custom properties
- Download and preview

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [BoundingBox Field Usage](#boundingbox-field-usage)
- [MediaLibrary Field Usage](#medialibrary-field-usage)
- [API Reference](#api-reference)
- [Data Structure](#data-structure)
- [Model Setup](#model-setup)
- [Development](#development)
- [Troubleshooting](#troubleshooting)

## Installation

### 1. Add Package Repository

This package is installed as a local VCS repository. It's already configured in PCR Card's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/nova-medialibrary-bounding-box-field",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

### 2. Require Package

```bash
composer require pcrcard/nova-medialibrary-bounding-box-field
```

### 3. Build Assets (if modifying package)

```bash
cd packages/nova-medialibrary-bounding-box-field
npm install
npm run prod
```

### 4. Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Quick Start

### Basic Bounding Box Field

```php
use DmitryBubyakin\NovaMedialibraryField\Fields\BoundingBoxField;

BoundingBoxField::make('Damage Assessment', 'bounding_boxes')
    ->image(fn() => $this->submissionImage?->file_url)
    ->readonly(false)
    ->help('Click and drag to mark damage areas');
```

### Basic MediaLibrary Field

```php
use DmitryBubyakin\NovaMedialibraryField\Fields\Medialibrary;

Medialibrary::make('Images', 'images')
    ->conversionOnView('thumb')
    ->croppable()
    ->multiple()
    ->rules('required');
```

## BoundingBox Field Usage

### Simple Read-Only Display

Display existing damage assessments without editing:

```php
BoundingBoxField::make('Damage Areas', 'bounding_boxes')
    ->image(fn() => $this->frontImage?->url)
    ->damageAssessments(fn() => $this->damageAssessments)
    ->readonly(true)
    ->help('View marked damage areas');
```

### Interactive Editor with Existing Data

Allow drawing new boxes while showing existing assessments:

```php
BoundingBoxField::make('Visual Damage Editor', 'bounding_boxes')
    ->image(function () {
        return $this->submissionImage?->file_url;
    })
    ->damageAssessments(function () {
        // Load related damage assessment models
        return $this->damageAssessments;
    })
    ->readonly(false)
    ->addressedMode(false)
    ->help('Draw bounding boxes to mark damaged areas')
    ->onlyOnForms();
```

### Multiple Images (Front/Back Cards)

Handle cards with front and back images:

```php
// Front image assessment
BoundingBoxField::make('Front Damage', 'front_bounding_boxes')
    ->image(fn() => $this->frontImage?->url)
    ->damageAssessments(fn() => $this->frontDamageAssessments)
    ->readonly(false)
    ->help('Mark damage on card front'),

// Back image assessment
BoundingBoxField::make('Back Damage', 'back_bounding_boxes')
    ->image(fn() => $this->backImage?->url)
    ->damageAssessments(fn() => $this->backDamageAssessments)
    ->readonly(false)
    ->help('Mark damage on card back'),
```

### Addressed Mode Toggle

Show only addressed vs all damage:

```php
// Show all damage (default)
BoundingBoxField::make('All Damage', 'all_damage')
    ->image(fn() => $this->image_url)
    ->damageAssessments(fn() => $this->damageAssessments)
    ->addressedMode(false),

// Show only addressed damage
BoundingBoxField::make('Addressed Damage', 'addressed_damage')
    ->image(fn() => $this->image_url)
    ->damageAssessments(fn() => $this->addressedDamageAssessments)
    ->addressedMode(true),
```

## MediaLibrary Field Usage

Complete documentation for the MediaLibrary field functionality.

### Basic Upload

```php
Medialibrary::make('Media', 'collection-name')
    ->multiple()
    ->croppable();
```

### Custom Fields

Define custom fields for media metadata:

```php
Medialibrary::make('Media')->fields(function () {
    return [
        Text::make('File Name', 'file_name')
            ->rules('required', 'min:2'),

        Text::make('Tooltip', 'custom_properties->tooltip')
            ->rules('required', 'min:2'),

        GeneratedConversions::make('Conversions')
            ->withTooltips(),
    ];
});
```

### Attach Existing Media

Allow selecting from existing media:

```php
// Display all media
Medialibrary::make('Media')->attachExisting();

// Display specific collection
Medialibrary::make('Media')->attachExisting('collectionName');

// Custom query
Medialibrary::make('Media')->attachExisting(function (Builder $query, Request $request, HasMedia $model) {
    $query->where('created_at', '>', now()->subDays(30));
});
```

### Cropping Configuration

Configure image cropping with Cropper.js options:

```php
Medialibrary::make('Media')
    ->croppable('conversionName', [
        'viewMode' => 3,
        'rotatable' => false,
        'zoomable' => true,
        'cropBoxResizable' => true,
    ]);
```

### Validation Rules

```php
Medialibrary::make('Media')
    ->rules('array', 'required')  // Collection rules
    ->creationRules('min:2')      // Creation-only rules
    ->updateRules('max:4')        // Update-only rules
    ->attachRules('image', 'dimensions:min_width=500,min_height=500'); // File rules
```

## API Reference

### BoundingBoxField Methods

#### `image($url)`
Set the image URL to annotate. Accepts string or closure.

**Parameters:**
- `$url` (string|Closure): Image URL or closure returning URL

**Example:**
```php
->image('https://example.com/image.jpg')
->image(fn() => $this->resource->image_url)
```

#### `damageAssessments($assessments)`
Load existing damage assessment models for display.

**Parameters:**
- `$assessments` (Collection|Closure): Assessment models or closure

**Example:**
```php
->damageAssessments($this->damageAssessments)
->damageAssessments(fn() => $this->resource->assessments()->where('addressed', false)->get())
```

#### `readonly($readonly = true)`
Enable/disable editing mode.

**Parameters:**
- `$readonly` (bool): True for display-only, false for editing

**Example:**
```php
->readonly(true)   // Display only
->readonly(false)  // Allow drawing
```

#### `addressedMode($enabled = true)`
Toggle between all damage and addressed-only views.

**Parameters:**
- `$enabled` (bool): True for addressed-only, false for all

**Example:**
```php
->addressedMode(true)   // Show only addressed
->addressedMode(false)  // Show all damage
```

#### `preserveOriginalProportions($preserve = true)`
Maintain original image aspect ratio.

**Parameters:**
- `$preserve` (bool): True to maintain aspect ratio

**Example:**
```php
->preserveOriginalProportions(true)   // Keep aspect ratio
->preserveOriginalProportions(false)  // Allow stretching
```

### Medialibrary Field Methods

For complete MediaLibrary field documentation, see:
- Original package: [dmitrybubyakin/nova-medialibrary-field](https://github.com/dmitrybubyakin/nova-medialibrary-field)
- Spatie docs: [Laravel MediaLibrary](https://spatie.be/docs/laravel-medialibrary)

Key methods include:
- `conversionOnView()`, `conversionOnIndexView()`, `conversionOnForm()`
- `croppable()`, `single()`, `multiple()`
- `attachExisting()`, `attachOnDetails()`, `autouploading()`
- `downloadUsing()`, `previewUsing()`, `tooltip()`, `title()`

## Data Structure

### Bounding Box JSON Format

Bounding boxes are stored as JSON arrays in your model:

```json
[
  {
    "id": "uuid-1234-5678-90ab",
    "x": 100,
    "y": 150,
    "width": 200,
    "height": 100,
    "image_width": 1000,
    "image_height": 1400,
    "rotation": 0,
    "damageType": "scratch",
    "severity": "moderate",
    "notes": "Deep scratch on right edge"
  },
  {
    "id": "uuid-abcd-efgh-ijkl",
    "x": 300,
    "y": 400,
    "width": 150,
    "height": 80,
    "image_width": 1000,
    "image_height": 1400,
    "rotation": 0,
    "damageType": "dent",
    "severity": "severe",
    "notes": "Corner dent affecting artwork"
  }
]
```

### Field Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `id` | string | Yes | Unique identifier (UUID) |
| `x` | number | Yes | X coordinate (pixels) |
| `y` | number | Yes | Y coordinate (pixels) |
| `width` | number | Yes | Box width (pixels) |
| `height` | number | Yes | Box height (pixels) |
| `image_width` | number | Yes | Original image width |
| `image_height` | number | Yes | Original image height |
| `rotation` | number | No | Rotation angle (default: 0) |
| `damageType` | string | No | Type of damage |
| `severity` | string | No | Severity level |
| `notes` | string | No | Additional notes |

### Damage Types

15 predefined damage types with color coding:

| Type | Icon | Color | Description |
|------|------|-------|-------------|
| `scratch` | 🔪 | Red (#EF4444) | Surface scratches |
| `dent` | 🔨 | Indigo (#6366F1) | Physical dents |
| `crease` | 📏 | Amber (#F59E0B) | Fold lines |
| `stain` | 🧽 | Purple (#A855F7) | Discoloration |
| `tear` | 📄 | Dark Red (#DC2626) | Rips and tears |
| `bend` | ↪️ | Green (#10B981) | Bent corners/edges |
| `edge_wear` | 🔲 | Blue (#3B82F6) | Edge deterioration |
| `corner_damage` | 📐 | Pink (#EC4899) | Corner wear |
| `water_damage` | 🌊 | Cyan (#06B6D4) | Water stains |
| `sun_damage` | ☀️ | Yellow (#FBBF24) | Light fading |
| `discoloration` | 🎨 | Lime (#84CC16) | Color changes |
| `whitening` | ⚪ | Gray (#9CA3AF) | White spots |
| `print_defect` | 🖨️ | Violet (#8B5CF6) | Manufacturing defects |
| `surface_damage` | 💥 | Orange (#F97316) | General surface issues |
| `other` | ❓ | Slate (#64748B) | Uncategorized |

### Severity Levels

| Severity | Color | Weight | Description |
|----------|-------|--------|-------------|
| `minor` | Green (#22C55E) | 1 | Minimal impact |
| `moderate` | Yellow (#EAB308) | 2 | Noticeable damage |
| `severe` | Red (#EF4444) | 3 | Significant damage |

## Model Setup

### Database Migration

```php
Schema::create('damage_assessments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('submission_trading_card_id')->constrained();
    $table->foreignId('submission_image_id')->nullable()->constrained();
    $table->string('damage_type');
    $table->string('severity')->default('moderate');
    $table->json('bounding_boxes')->nullable();
    $table->text('notes')->nullable();
    $table->boolean('addressed')->default(false);
    $table->timestamps();
});
```

### Model Configuration

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TradingCard extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $casts = [
        'bounding_boxes' => 'array',
    ];

    protected $fillable = [
        'name',
        'bounding_boxes',
    ];

    public function damageAssessments()
    {
        return $this->hasMany(DamageAssessment::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('card-images')
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(300)
                    ->height(300);

                $this->addMediaConversion('large')
                    ->width(1600)
                    ->height(1200);
            });
    }
}
```

### Complete Nova Resource Example

```php
<?php

namespace App\Nova;

use Laravel\Nova\Resource;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use DmitryBubyakin\NovaMedialibraryField\Fields\Medialibrary;
use DmitryBubyakin\NovaMedialibraryField\Fields\BoundingBoxField;

class TradingCard extends Resource
{
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Card Name', 'name')->required(),

            // Image upload with MediaLibrary
            Medialibrary::make('Card Images', 'card-images')
                ->conversionOnView('large')
                ->conversionOnIndexView('thumb')
                ->multiple()
                ->croppable()
                ->help('Upload card images for assessment'),

            // Interactive damage assessment with BoundingBoxField
            BoundingBoxField::make('Damage Assessment', 'bounding_boxes')
                ->image(function () {
                    $latestImage = $this->getFirstMedia('card-images');
                    return $latestImage ? $latestImage->getUrl('large') : null;
                })
                ->damageAssessments(fn() => $this->damageAssessments)
                ->readonly(false)
                ->help('Mark damage areas on the card image')
                ->hideFromIndex(),

            // Read-only damage overview on detail view
            BoundingBoxField::make('Damage Overview', 'damage_summary')
                ->image(fn() => $this->getFirstMedia('card-images')?->getUrl('large'))
                ->damageAssessments(fn() => $this->damageAssessments)
                ->readonly(true)
                ->onlyOnDetail(),
        ];
    }
}
```

## Development

### Package Structure

```
packages/nova-medialibrary-bounding-box-field/
├── src/
│   ├── Fields/
│   │   ├── Medialibrary.php         # Standard media field
│   │   └── BoundingBoxField.php      # Bounding box field
│   ├── Resources/
│   │   └── Media.php
│   └── FieldServiceProvider.php
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   ├── Medialibrary/        # Media management components
│   │   │   └── BoundingBox/         # Bounding box editor components
│   │   └── field.js                 # Component registration
│   └── sass/
│       └── field.scss
├── dist/                            # Compiled assets
│   ├── js/field.js
│   ├── css/field.css
│   └── mix-manifest.json
├── composer.json
├── package.json
├── webpack.mix.js
└── README.md
```

### Building Assets

```bash
cd packages/nova-medialibrary-bounding-box-field

# Install dependencies
npm install

# Development build (with source maps)
npm run dev

# Production build (minified)
npm run prod

# Watch for changes
npm run watch
```

### Testing Changes

After making code changes:

```bash
# Rebuild package assets
cd packages/nova-medialibrary-bounding-box-field
npm run prod

# Clear Laravel caches
cd ../..
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Rebuild application assets
npm run dev
```

## Troubleshooting

### Assets Not Loading

**Problem**: Bounding box components not rendering

**Solution**:
```bash
# Clear Nova cache
php artisan nova:clear-cache

# Rebuild assets
cd packages/nova-medialibrary-bounding-box-field
npm run prod

# Clear application cache
php artisan cache:clear
```

### Bounding Boxes Not Saving

**Problem**: Data not persisting to database

**Solution**: Ensure model casts attribute as array:
```php
protected $casts = [
    'bounding_boxes' => 'array',
];
```

### Image Not Displaying

**Problem**: Image URL returns 404

**Solution**: Verify image URL is publicly accessible:
```php
// Debug image URL
dd($this->submissionImage?->file_url);

// Check media conversions
dd($this->getFirstMedia('collection-name')?->getUrl('conversion-name'));
```

### Component Not Registered

**Problem**: Vue component not found errors

**Solution**: Check service provider is loaded:
```bash
php artisan config:cache
php artisan route:list | grep nova-vendor/pcrcard
```

### Webpack Build Errors

**Problem**: `Cannot find module 'laravel-nova-devtool'`

**Solution**: Package already configured for Nova 5.x with externals. If errors persist:
```bash
# Remove node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
npm run prod
```

## Credits

- **Original Package**: [dmitrybubyakin/nova-medialibrary-field](https://github.com/dmitrybubyakin/nova-medialibrary-field)
- **Spatie MediaLibrary**: [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary)
- **PCR Card Enhancements**: BoundingBoxField for visual damage assessment

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Changelog

### v2.0.0 - October 2025
- ✨ **NEW**: Added `BoundingBoxField` for interactive visual annotation
- ✨ **NEW**: 15 damage types with color coding and icons
- ✨ **NEW**: 3 severity levels for damage classification
- ✨ **NEW**: Canvas-based drawing interface with multi-box support
- ✨ **NEW**: Read-only and addressed mode toggles
- ✨ **NEW**: Comprehensive API and usage documentation
- 🔧 Updated for Laravel Nova 5.x compatibility
- 🔧 Removed Nova 4.x devtool dependency
- 🔧 Configured webpack externals for Nova dependencies
- 🐛 Fixed cropperjs CSS import issue

### v1.0.0 - Original Release
- 📦 MediaLibrary field functionality
- 🖼️ Image upload, cropping, and management
- 🎨 Multiple file support with conversions

## Support

For issues related to:
- **MediaLibrary functionality**: See [original package documentation](https://github.com/dmitrybubyakin/nova-medialibrary-field)
- **BoundingBox functionality**: Contact PCR Card development team
- **Spatie MediaLibrary**: See [Spatie documentation](https://spatie.be/docs/laravel-medialibrary)
