<?php
/**
 * Helper functions for the application
 */

/**
 * Function to handle pagination
 * 
 * @param PDO $conn Database connection
 * @param string $baseQuery Base SQL query without LIMIT and OFFSET
 * @param string $countQuery SQL query to count total records
 * @param array $params Parameters for the query
 * @param int $page Current page number
 * @param int $limit Number of records per page
 * @return array Array containing records, total pages, and total records
 */
function paginateQuery($conn, $baseQuery, $countQuery, $params = [], $page = 1, $limit = 10) {
    try {
        // Validate inputs
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        
        // Count total records
        $stmt = $conn->prepare($countQuery);
        
        // Bind parameters for count query
        $paramIndex = 1;
        foreach ($params as $param) {
            if (is_int($param)) {
                $stmt->bindValue($paramIndex++, $param, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        $totalRecords = $stmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);
        
        // Get records with pagination
        $fullQuery = $baseQuery . " LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($fullQuery);
        
        // Bind parameters for main query
        $paramIndex = 1;
        foreach ($params as $param) {
            if (is_int($param)) {
                $stmt->bindValue($paramIndex++, $param, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
            }
        }
        
        // Bind LIMIT and OFFSET as integers
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $records = $stmt->fetchAll();
        
        return [
            'records' => $records,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'currentPage' => $page,
            'limit' => $limit
        ];
    } catch (PDOException $e) {
        // Log error
        error_log("Pagination error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Function to generate pagination links
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $baseUrl Base URL for pagination links
 * @param array $queryParams Additional query parameters
 * @return string HTML for pagination links
 */
function generatePaginationLinks($currentPage, $totalPages, $baseUrl, $queryParams = []) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevParams = array_merge($queryParams, ['page' => $currentPage - 1]);
        $prevUrl = $baseUrl . '?' . http_build_query($prevParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $firstParams = array_merge($queryParams, ['page' => 1]);
        $firstUrl = $baseUrl . '?' . http_build_query($firstParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . $firstUrl . '">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $pageParams = array_merge($queryParams, ['page' => $i]);
        $pageUrl = $baseUrl . '?' . http_build_query($pageParams);
        $activeClass = ($i === $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
        $lastParams = array_merge($queryParams, ['page' => $totalPages]);
        $lastUrl = $baseUrl . '?' . http_build_query($lastParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . $lastUrl . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextParams = array_merge($queryParams, ['page' => $currentPage + 1]);
        $nextUrl = $baseUrl . '?' . http_build_query($nextParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Function to log errors to file
 * 
 * @param string $message Error message
 * @param string $level Error level (ERROR, WARNING, INFO)
 * @param string $file File where error occurred
 * @param int $line Line number where error occurred
 * @return void
 */
function logError($message, $level = 'ERROR', $file = '', $line = 0) {
    $logDir = __DIR__ . '/../logs';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
    
    // Format the log message
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level]";
    
    if ($file && $line) {
        $logMessage .= " [$file:$line]";
    }
    
    $logMessage .= ": $message" . PHP_EOL;
    
    // Write to log file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Function to validate input data
 * 
 * @param array $data Input data to validate
 * @param array $rules Validation rules
 * @return array Array containing validation status and errors
 */
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        // Skip if field doesn't exist and is not required
        if (!isset($data[$field]) && !in_array('required', $rule)) {
            continue;
        }
        
        $value = isset($data[$field]) ? $data[$field] : '';
        
        // Check each rule
        foreach ($rule as $r) {
            if ($r === 'required' && empty($value)) {
                $errors[$field][] = "Field $field is required";
            } elseif (strpos($r, 'min:') === 0) {
                $min = (int)substr($r, 4);
                if (strlen($value) < $min) {
                    $errors[$field][] = "Field $field must be at least $min characters";
                }
            } elseif (strpos($r, 'max:') === 0) {
                $max = (int)substr($r, 4);
                if (strlen($value) > $max) {
                    $errors[$field][] = "Field $field must be at most $max characters";
                }
            } elseif ($r === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field][] = "Field $field must be a valid email address";
            } elseif ($r === 'numeric' && !is_numeric($value)) {
                $errors[$field][] = "Field $field must be numeric";
            } elseif (strpos($r, 'in:') === 0) {
                $allowedValues = explode(',', substr($r, 3));
                if (!in_array($value, $allowedValues)) {
                    $errors[$field][] = "Field $field must be one of: " . implode(', ', $allowedValues);
                }
            }
        }
    }
    
    return [
        'isValid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Function to format date to Indonesian format
 * 
 * @param string $date Date in Y-m-d format
 * @param bool $withDay Include day name
 * @return string Formatted date
 */
function formatDate($date, $withDay = false) {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $days = [
        'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
    ];
    
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    
    if ($withDay) {
        $dayName = $days[date('w', $timestamp)];
        return "$dayName, $day $month $year";
    }
    
    return "$day $month $year";
}

/**
 * Function to format time to Indonesian format
 * 
 * @param string $time Time in H:i:s format
 * @return string Formatted time
 */
function formatTime($time) {
    if (empty($time)) {
        return '';
    }
    
    $timestamp = strtotime($time);
    return date('H:i', $timestamp);
}

/**
 * Function to generate random password
 * 
 * @param int $length Password length
 * @return string Random password
 */
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}
?>
