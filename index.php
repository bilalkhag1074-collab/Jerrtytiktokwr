<?php
header('Content-Type: application/json');

/**
 * TikTok ID Info Tool - Real API Integration
 * Using SociaVault API for real TikTok data
 * Correct Endpoint: https://api.sociavault.com/v1/scrape/tiktok/profile
 */

// API Configuration
define('SOCIAVAULT_API_KEY', 'sk_live_3f5454ffe90bf1ef3a307c95efa6541e');
define('SOCIAVAULT_API_URL', 'https://api.sociavault.com/v1/scrape/tiktok/profile');

$response = [
    'success' => false,
    'data' => null,
    'error' => null
];

// Get username from request
$username = isset($_GET['username']) ? trim($_GET['username']) : null;

if (!$username) {
    $response['error'] = 'Username is required';
    echo json_encode($response);
    exit;
}

// Remove @ symbol if present
$username = ltrim($username, '@');

/**
 * Fetch TikTok user info using SociaVault API
 */
function getTikTokUserInfo($username) {
    $api_key = SOCIAVAULT_API_KEY;
    $api_url = SOCIAVAULT_API_URL;
    
    // Build URL with query parameter
    $url = $api_url . '?handle=' . urlencode($username);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . $api_key,
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['error' => 'CURL Error: ' . $curlError];
    }
    
    if ($httpCode !== 200) {
        return ['error' => 'API returned HTTP ' . $httpCode];
    }
    
    $data = json_decode($result, true);
    
    if (!$data) {
        return ['error' => 'Invalid JSON response'];
    }
    
    return $data;
}

// Get user data from API
$apiResponse = getTikTokUserInfo($username);

if (isset($apiResponse['error'])) {
    $response['error'] = $apiResponse['error'];
    echo json_encode($response);
    exit;
}

// Check if API response is successful
if (isset($apiResponse['success']) && $apiResponse['success'] && isset($apiResponse['data'])) {
    $userData = $apiResponse['data'];
    
    // Extract user and stats data
    $user = $userData['user'] ?? [];
    $stats = $userData['stats'] ?? $userData['statsV2'] ?? [];
    
    $response['success'] = true;
    $response['data'] = [
        'username' => $user['uniqueId'] ?? $username,
        'userId' => $user['id'] ?? 'N/A',
        'nickname' => $user['nickname'] ?? 'N/A',
        'avatar' => $user['avatarLarger'] ?? $user['avatarMedium'] ?? 'N/A',
        'bio' => $user['signature'] ?? 'No bio',
        'verified' => $user['verified'] ?? false,
        'followers' => number_format($stats['followerCount'] ?? 0),
        'following' => number_format($stats['followingCount'] ?? 0),
        'likes' => number_format($stats['heartCount'] ?? $stats['heart'] ?? 0),
        'videos' => number_format($stats['videoCount'] ?? 0),
        'region' => 'N/A',
        'source' => 'Real TikTok API Data'
    ];
} else {
    $response['error'] = 'Failed to fetch user data. User may not exist.';
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>

