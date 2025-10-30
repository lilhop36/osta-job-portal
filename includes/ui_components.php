<?php
/**
 * UI Components for OSTA Job Portal
 * Provides reusable UI components for consistent design across the portal
 */

/**
 * Display a card component
 * 
 * @param string $title Card title
 * @param string $content Card content (HTML)
 * @param array $options Additional options (icon, footer, class, etc.)
 * @return string HTML for the card
 */
function ui_card($title, $content, $options = []) {
    $icon = isset($options['icon']) ? $options['icon'] : '';
    $footer = isset($options['footer']) ? $options['footer'] : '';
    $class = isset($options['class']) ? $options['class'] : '';
    $headerClass = isset($options['header_class']) ? $options['header_class'] : 'bg-white';
    
    $html = '<div class="card shadow-sm mb-4 ' . htmlspecialchars($class) . '">';
    
    // Card header
    if ($title) {
        $html .= '<div class="card-header ' . $headerClass . '">';
        if ($icon) {
            $html .= '<i class="' . htmlspecialchars($icon) . ' me-2"></i>';
        }
        $html .= '<h5 class="card-title mb-0">' . htmlspecialchars($title) . '</h5>';
        $html .= '</div>';
    }
    
    // Card body
    $html .= '<div class="card-body">' . $content . '</div>';
    
    // Card footer
    if ($footer) {
        $html .= '<div class="card-footer bg-transparent">' . $footer . '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Display an alert message
 * 
 * @param string $message The message to display
 * @param string $type Alert type (success, danger, warning, info)
 * @param bool $dismissible Whether the alert can be dismissed
 * @return string HTML for the alert
 */
function ui_alert($message, $type = 'info', $dismissible = true) {
    $class = 'alert alert-' . $type;
    if ($dismissible) {
        $class .= ' alert-dismissible fade show';
    }
    
    $html = '<div class="' . $class . '" role="alert">';
    $html .= $message;
    if ($dismissible) {
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Display a badge
 * 
 * @param string $text Badge text
 * @param string $type Badge type (primary, secondary, success, etc.)
 * @param string $icon Optional icon class
 * @return string HTML for the badge
 */
function ui_badge($text, $type = 'primary', $icon = '') {
    $html = '<span class="badge bg-' . $type . ' rounded-pill">';
    if ($icon) {
        $html .= '<i class="' . $icon . ' me-1"></i>';
    }
    $html .= htmlspecialchars($text) . '</span>';
    
    return $html;
}

/**
 * Display a button
 * 
 * @param string $text Button text
 * @param string $url Button URL
 * @param string $type Button type (primary, secondary, success, etc.)
 * @param array $options Additional options (icon, size, class, etc.)
 * @return string HTML for the button
 */
function ui_button($text, $url = '#', $type = 'primary', $options = []) {
    $icon = isset($options['icon']) ? $options['icon'] : '';
    $size = isset($options['size']) ? 'btn-' . $options['size'] : '';
    $class = isset($options['class']) ? $options['class'] : '';
    $isOutline = isset($options['outline']) ? $options['outline'] : false;
    $isBlock = isset($options['block']) ? $options['block'] : false;
    $isDisabled = isset($options['disabled']) ? $options['disabled'] : false;
    
    $btnClass = 'btn ' . ($isOutline ? 'btn-outline-' : 'btn-') . $type;
    if ($size) $btnClass .= ' ' . $size;
    if ($class) $btnClass .= ' ' . $class;
    if ($isBlock) $btnClass .= ' w-100';
    if ($isDisabled) $btnClass .= ' disabled';
    
    $html = '<a href="' . htmlspecialchars($url) . '" class="' . $btnClass . '"';
    if ($isDisabled) $html .= ' aria-disabled="true" tabindex="-1"';
    $html .= '>';
    
    if ($icon) {
        $html .= '<i class="' . $icon . ' me-1"></i> ';
    }
    
    $html .= htmlspecialchars($text) . '</a>';
    
    return $html;
}

/**
 * Display a loading spinner
 * 
 * @param string $size Size class (sm, md, lg)
 * @param string $color Spinner color (primary, secondary, etc.)
 * @return string HTML for the spinner
 */
function ui_loading_spinner($size = 'md', $color = 'primary') {
    $sizeClass = '';
    switch ($size) {
        case 'sm': $sizeClass = 'spinner-border-sm'; break;
        case 'lg': $sizeClass = 'spinner-border-lg'; break;
    }
    
    return '<div class="d-flex justify-content-center">
        <div class="spinner-border text-' . $color . ' ' . $sizeClass . '" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>';
}

/**
 * Display a page header with breadcrumbs
 * 
 * @param string $title Page title
 * @param array $breadcrumbs Array of breadcrumb items [['url' => '', 'text' => '']]
 * @return string HTML for the page header
 */
function ui_page_header($title, $breadcrumbs = []) {
    $html = '<div class="page-header py-4 mb-4 border-bottom">';
    $html .= '<div class="container">';
    $html .= '<div class="row align-items-center">';
    $html .= '<div class="col">';
    $html .= '<h1 class="h3 mb-0">' . htmlspecialchars($title) . '</h1>';
    $html .= '</div>';
    
    if (!empty($breadcrumbs)) {
        $html .= '<div class="col-auto">';
        $html .= '<nav aria-label="breadcrumb">';
        $html .= '<ol class="breadcrumb mb-0">';
        
        foreach ($breadcrumbs as $item) {
            $isActive = empty($item['url']);
            $html .= '<li class="breadcrumb-item' . ($isActive ? ' active' : '') . '"' . 
                     ($isActive ? ' aria-current="page"' : '') . '>';
            
            if (!$isActive) {
                $html .= '<a href="' . htmlspecialchars($item['url']) . '">';
            }
            
            $html .= htmlspecialchars($item['text']);
            
            if (!$isActive) {
                $html .= '</a>';
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ol>';
        $html .= '</nav>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
