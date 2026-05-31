<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OCR Engine
    |--------------------------------------------------------------------------
    |
    | The OCR engine to use for text extraction.
    | Currently only Tesseract is supported.
    |
    */

    'engine' => env('OCR_ENGINE', 'tesseract'),

    /*
    |--------------------------------------------------------------------------
    | GPU Acceleration
    |--------------------------------------------------------------------------
    |
    | Enable GPU acceleration for OCR processing.
    | Requires CUDA and cuDNN to be installed.
    | Recommended for production with high volume.
    |
    */

    'use_gpu' => env('OCR_USE_GPU', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Enable caching of OCR results to improve performance.
    | Results are cached based on image file hash.
    |
    */

    'cache' => [
        'enabled' => env('OCR_CACHE_ENABLED', true),
        'ttl' => env('OCR_CACHE_TTL', 604800), // 7 days in seconds
        'prefix' => 'ocr_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tesseract Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to Tesseract OCR engine.
    |
    */

    'tesseract' => [
        'languages' => ['ind', 'eng'], // Indonesian and English
        'psm' => 3, // Page segmentation mode
        'preprocessing' => true, // Enable image preprocessing
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported File Formats
    |--------------------------------------------------------------------------
    |
    | List of supported image formats for OCR processing.
    |
    */

    'supported_formats' => ['png', 'jpg', 'jpeg', 'pdf', 'bmp', 'webp'],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for image preprocessing before OCR.
    |
    */

    'image_processing' => [
        'max_width' => 2000, // Maximum image width (pixels)
        'max_height' => 2000, // Maximum image height (pixels)
        'quality' => 90, // JPEG quality (1-100)
        'auto_resize' => true, // Automatically resize large images
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for batch OCR processing.
    |
    */

    'batch' => [
        'max_images' => 15, // Maximum images per batch
        'parallel' => false, // Enable parallel processing (requires queue)
        'chunk_size' => 5, // Process images in chunks
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for OCR operations.
    |
    */

    'logging' => [
        'enabled' => env('OCR_LOGGING_ENABLED', true),
        'level' => env('OCR_LOGGING_LEVEL', 'info'), // debug, info, warning, error
        'log_text' => false, // Log extracted text (may be verbose)
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Strategy
    |--------------------------------------------------------------------------
    |
    | Define fallback strategy when primary OCR engine fails.
    | Order: tesseract -> gd_fallback
    |
    */

    'fallback' => [
        'enabled' => true,
        'strategy' => 'cascade', // cascade, parallel, or none
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Optimization
    |--------------------------------------------------------------------------
    |
    | Performance optimization settings.
    |
    */

    'performance' => [
        'memory_limit' => '512M', // PHP memory limit for OCR
        'max_execution_time' => 300, // Maximum execution time (seconds)
        'queue_enabled' => false, // Process OCR in background queue
        'queue_name' => 'ocr', // Queue name for OCR jobs
    ],

];
