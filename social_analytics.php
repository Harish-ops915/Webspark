<?php
include "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get linked social accounts
$stmt = $mysqli->prepare("SELECT * FROM social_accounts WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$social_accounts = $stmt->get_result();

$analytics_data = [];

while ($account = $social_accounts->fetch_assoc()) {
    $platform = $account['platform'];
    $account_id = $account['account_id'];
    
    switch ($platform) {
        case 'facebook':
            $data = getFacebookAnalytics($account_id, $account['access_token']);
            break;
        case 'twitter':
            $data = getTwitterAnalytics($account_id, $account['access_token']);
            break;
        case 'instagram':
            $data = getInstagramAnalytics($account_id, $account['access_token']);
            break;
        case 'linkedin':
            $data = getLinkedInAnalytics($account_id, $account['access_token']);
            break;
        default:
            $data = null;
    }
    
    if ($data) {
        $analytics_data[$platform] = $data;
        
        // Store analytics data in database
        foreach ($data as $metric_name => $metric_value) {
            $stmt = $mysqli->prepare("INSERT INTO social_analytics (user_id, platform, metric_name, metric_value, date_recorded) VALUES (?, ?, ?, ?, CURDATE()) ON DUPLICATE KEY UPDATE metric_value=VALUES(metric_value)");
            $stmt->bind_param("issi", $user_id, $platform, $metric_name, $metric_value);
            $stmt->execute();
        }
    }
}

function getFacebookAnalytics($page_id, $access_token) {
    if (!$access_token) {
        return null;
    }
    
    try {
        $url = "https://graph.facebook.com/v18.0/{$page_id}/insights?metric=page_views,page_engaged_users,page_impressions&period=day&since=" . date('Y-m-d', strtotime('-7 days')) . "&until=" . date('Y-m-d') . "&access_token={$access_token}";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Webspark/1.0'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);
        
        if (isset($data['data']) && is_array($data['data'])) {
            $analytics = [];
            foreach ($data['data'] as $metric) {
                $metric_name = $metric['name'];
                $total_value = 0;
                
                if (isset($metric['values']) && is_array($metric['values'])) {
                    foreach ($metric['values'] as $value) {
                        $total_value += isset($value['value']) ? intval($value['value']) : 0;
                    }
                }
                
                $analytics[$metric_name] = $total_value;
            }
            return $analytics;
        }
    } catch (Exception $e) {
        error_log("Facebook Analytics Error: " . $e->getMessage());
    }
    
    return null;
}

function getTwitterAnalytics($username, $access_token) {
    if (!$access_token) {
        return null;
    }
    
    try {
        // Twitter API v2 requires OAuth 2.0
        $url = "https://api.twitter.com/2/users/by/username/{$username}?user.fields=public_metrics";
        
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'User-Agent: Webspark/1.0'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['data']['public_metrics'])) {
            return [
                'followers_count' => $data['data']['public_metrics']['followers_count'],
                'following_count' => $data['data']['public_metrics']['following_count'],
                'tweet_count' => $data['data']['public_metrics']['tweet_count'],
                'listed_count' => $data['data']['public_metrics']['listed_count']
            ];
        }
    } catch (Exception $e) {
        error_log("Twitter Analytics Error: " . $e->getMessage());
    }
    
    return null;
}

function getInstagramAnalytics($account_id, $access_token) {
    if (!$access_token) {
        return null;
    }
    
    try {
        // Instagram Basic Display API
        $url = "https://graph.instagram.com/{$account_id}?fields=account_type,media_count&access_token={$access_token}";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Webspark/1.0'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);
        
        if (isset($data['media_count'])) {
            return [
                'media_count' => $data['media_count'],
                'account_type' => $data['account_type'] ?? 'PERSONAL'
            ];
        }
    } catch (Exception $e) {
        error_log("Instagram Analytics Error: " . $e->getMessage());
    }
    
    return null;
}

function getLinkedInAnalytics($company_id, $access_token) {
    if (!$access_token) {
        return null;
    }
    
    try {
        // LinkedIn Marketing API
        $url = "https://api.linkedin.com/v2/organizationalEntityFollowerStatistics?q=organizationalEntity&organizationalEntity={$company_id}";
        
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'User-Agent: Webspark/1.0'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['elements'][0]['followerCountsByAssociationType'][0]['followerCounts'])) {
            $follower_data = $data['elements'][0]['followerCountsByAssociationType'][0]['followerCounts'];
            return [
                'total_followers' => $follower_data['organicFollowerCount'] + $follower_data['paidFollowerCount']
            ];
        }
    } catch (Exception $e) {
        error_log("LinkedIn Analytics Error: " . $e->getMessage());
    }
    
    return null;
}

echo json_encode(['success' => true, 'data' => $analytics_data]);
?>
