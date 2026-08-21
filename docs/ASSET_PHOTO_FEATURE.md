# Asset Photo Feature - Implementation Guide

## Overview

This feature adds photo upload capability to the Asset Management module with support for:
- File upload (drag & drop or browse)
- Camera capture (mobile/webcam)
- Image preview
- Thumbnail generation
- Lightbox display
- Photo deletion

## Features Implemented

### 1. Upload Methods
- **Drag & Drop**: Drag image files directly onto the upload zone
- **Browse**: Click to select files via file picker
- **Camera Capture**: Take photos using device camera (getUserMedia API)

### 2. Supported Formats
- JPG / JPEG
- PNG
- WebP
- Maximum file size: 5 MB

### 3. Storage
- Images stored in `storage/app/public/assets/`
- Thumbnails generated in `storage/app/public/assets/` (prefixed with `thumb_`)
- Uses Laravel Storage facade

## Files Created

### New Files

| File | Description |
|------|-------------|
| `database/migrations/2026_06_30_000001_add_asset_photo_columns.php` | Migration for photo metadata columns |
| `resources/views/crm/assets/partials/photo-upload.blade.php` | Photo upload form section (create) |
| `resources/views/crm/assets/partials/photo-upload-scripts.blade.php` | JavaScript for upload/camera/lightbox |
| `resources/views/crm/assets/partials/photo-edit.blade.php` | Photo section with edit existing photo support |
| `docs/ASSET_PHOTO_FEATURE.md` | This documentation |

### Files Modified

| File | Changes |
|------|---------|
| `app/Models/Asset.php` | Added photo accessors, thumbnail generation, photo deletion |
| `app/Http/Requests/AssetRequest.php` | Added photo validation rules |
| `app/Http/Controllers/CRM/AssetController.php` | Added photo handling in store/update/delete |
| `resources/views/crm/assets/create.blade.php` | Added photo upload section, enctype |
| `resources/views/crm/assets/edit.blade.php` | Added photo edit section with change/delete |
| `resources/views/crm/assets/show.blade.php` | Added photo display with lightbox |
| `resources/views/crm/assets/index.blade.php` | Added thumbnail in list view |
| `routes/web.php` | Added route for photo deletion |

## Database Schema

### New Columns in `assets` Table

| Column | Type | Description |
|--------|------|-------------|
| `thumbnail_path` | varchar(500) | Path to generated thumbnail |
| `original_filename` | varchar(255) | Original uploaded filename |
| `file_size` | unsigned int | File size in bytes |
| `mime_type` | varchar(50) | Image MIME type |

Existing column `image_path` stores the main image path.

## API Endpoints

### Photo Upload
```
POST /assets (multipart/form-data)
```
Include `photo` field in form data.

### Photo Deletion
```
DELETE /assets/{asset}/photo
```
Deletes photo files from storage and clears database fields.

## Validation Rules

```php
'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120'
```

- `nullable`: Photo is optional
- `image`: Must be an image file
- `mimes`: Allowed formats
- `max:5120`: Maximum 5MB (5120KB)

## Usage

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Create Storage Link
```bash
php artisan storage:link
```

### 3. Ensure Intervention Image is Installed
The thumbnail generation requires Intervention Image:
```bash
composer require intervention/image
```

If not installed, thumbnails won't be generated but main images will still work.

## Screenshots

### Create Asset - Photo Upload Section
```
┌─────────────────────────────────────────────────────────────┐
│ 📷 Asset Photo                              JPG, PNG, WebP • │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│     ┌───────────────────────────────────────────────────┐   │
│     │                                                   │   │
│     │     📁                                              │   │
│     │                                                   │   │
│     │     Drag & drop image here                        │   │
│     │                                                   │   │
│     │     or                                             │   │
│     │                                                   │   │
│     │     [ 📤 Upload Photo ]                           │   │
│     │                                                   │   │
│     └───────────────────────────────────────────────────┘   │
│                                                             │
│     ┌─────────────────────────────────────────────────────┐ │
│     │ [ 📷 Open Camera ]                                  │ │
│     └─────────────────────────────────────────────────────┘ │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Edit Asset - Existing Photo
```
┌─────────────────────────────────────────────────────────────┐
│ 📷 Asset Photo                              JPG, PNG, WebP • │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│     ┌──────────┐                                            │
│     │          │  asset-photo.jpg                          │
│     │   [IMG]  │  1.2 MB                                    │
│     │          │                                           │
│     └──────────┘                                            │
│                                                             │
│     [ 🔄 Change ]  [ 🗑️ Remove ]                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Show Asset - Photo with Lightbox
```
┌─────────────────────────────────────────────────────────────┐
│ 📷 Asset Photo                                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│         ┌─────────────────┐                                 │
│         │                 │                                 │
│         │    [  IMG  ]    │  ← Click to open lightbox      │
│         │                 │                                 │
│         └─────────────────┘                                 │
│           asset-photo.jpg                                    │
│           1.2 MB                                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Asset List - Thumbnail
```
┌─────────────────────────────────────────────────────────────┐
│ Asset          Category      Status    Location   Actions │
├─────────────────────────────────────────────────────────────┤
│ ┌────┐        ┌─────────┐  ┌────────┐             👁️ 🖊️ 🗑️ │
│ │IMG │ Laptop  Electronics  Available   Jakarta            │
│ └────┘ AST-...                                               │
│ ┌────┐        ┌─────────┐  ┌────────┐             👁️ 🖊️ 🗑️ │
│ │    │ Monitor Furniture   Maintenance Room 101            │
│ └────┘ AST-...                                               │
└─────────────────────────────────────────────────────────────┘
```

## Lightbox Preview
```
┌─────────────────────────────────────────────────────────────┐
│ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░┌───────────────────────┐░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░│                       │░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░│                       │░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░│      [ LARGE IMG ]   │░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░│                       │░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░│                       │░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░└───────────────────────┘░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│                                          [ ⬇️ Download ]    │
└─────────────────────────────────────────────────────────────┘
```

## Logging

Photo actions are logged with the following information:

```json
{
    "action": "uploaded|updated|deleted",
    "asset_uuid": "uuid",
    "filename": "original_filename.jpg",
    "user_id": 1,
    "user_email": "user@example.com",
    "ip": "192.168.1.1",
    "timestamp": "2026-06-30T12:00:00+00:00"
}
```

## Troubleshooting

### Camera Not Working
1. Ensure HTTPS is enabled (camera API requires secure context)
2. Check browser permissions for camera access
3. Some browsers require user interaction before accessing camera

### Thumbnails Not Generated
1. Install Intervention Image: `composer require intervention/image`
2. Check storage permissions
3. Check logs for errors

### Image Not Displaying
1. Run `php artisan storage:link`
2. Check storage disk configuration in `config/filesystems.php`
3. Verify file exists in `storage/app/public/assets/`
