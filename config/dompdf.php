<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Set some default values. It is possible to add all defines that can be set
    | in dompdf_config.inc.php. You can also override the entire config file.
    |
    */
    'show_warnings' => (bool) env('DOMPDF_SHOW_WARNINGS', false),

    'orientation' => env('DOMPDF_ORIENTATION', 'portrait'),

    'defines' => [
        'font_dir' => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),
        'temp_dir' => sys_get_temp_dir(),
        'chroot' => realpath(base_path()),
        'pre_flight' => true,
        'enable_font_subsetting' => true,
        'enable_remote' => true,
        'log_html_file' => false,
        'default_font' => 'DejaVu Sans',
        'pdf_background' => true,
        'default_media_type' => 'screen',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',
        'dpi' => 96,
        'enable_css_float' => true,
        'enable_javascript' => true,
        'enable_html5_parser' => true,
        'font_height_ratio' => 1.1,
        'debug_png' => false,
        'debug_keep_temp' => false,
        'debug_css' => false,
        'debug_layout' => false,
        'debug_layout_lines' => false,
        'debug_layout_blocks' => false,
        'debug_layout_inline' => false,
        'debug_layout_padding_box' => false,
        'pdf_background' => false,
        'pdflib_license' => env('DOMPDF_PDFLIB_LICENSE', ''),
    ],

];
