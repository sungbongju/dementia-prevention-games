<?php
// api.php - 게임 기록 API
// PHP 5.4 호환 버전
// yut_score -> gonogo_score (색상 패턴 기억 게임)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_config.php';

// PHP 5.4 호환: ?? 연산자 대신 isset 사용
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    
    // 게임 기록 저장
    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || empty($data['player_name'])) {
            echo json_encode(array('success' => false, 'error' => '이름을 입력해주세요.'));
            exit;
        }
        
        $playerName = trim($data['player_name']);
        
        // 해당 플레이어의 최대 회차 조회
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(session_number), 0) as max_session FROM game_records WHERE player_name = ?");
        $stmt->execute(array($playerName));
        $result = $stmt->fetch();
        $sessionNumber = $result['max_session'] + 1;
        
        // 기록 저장 (gonogo_score)
        $stmt = $pdo->prepare("
            INSERT INTO game_records 
            (player_name, session_number, stroop_score, gonogo_score, nback_score, pal_score, ufov_score, trail_score, total_score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stroop = isset($data['stroop_score']) ? intval($data['stroop_score']) : 0;
        $gonogo = isset($data['gonogo_score']) ? intval($data['gonogo_score']) : 0;
        $nback = isset($data['nback_score']) ? intval($data['nback_score']) : 0;
        $pal = isset($data['pal_score']) ? intval($data['pal_score']) : 0;
        $ufov = isset($data['ufov_score']) ? intval($data['ufov_score']) : 0;
        $trail = isset($data['trail_score']) ? intval($data['trail_score']) : 0;
        $total = $stroop + $gonogo + $nback + $pal + $ufov + $trail;
        
        $stmt->execute(array(
            $playerName,
            $sessionNumber,
            $stroop,
            $gonogo,
            $nback,
            $pal,
            $ufov,
            $trail,
            $total
        ));
        
        echo json_encode(array(
            'success' => true,
            'message' => '기록이 저장되었습니다!',
            'session_number' => $sessionNumber,
            'record_id' => $pdo->lastInsertId()
        ));
        break;
    
    // 개인 기록 조회
    case 'get_records':
        $playerName = isset($_GET['player_name']) ? $_GET['player_name'] : '';
        
        if (empty($playerName)) {
            echo json_encode(array('success' => false, 'error' => '이름을 입력해주세요.'));
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM game_records 
            WHERE player_name = ? 
            ORDER BY session_number DESC 
            LIMIT 20
        ");
        $stmt->execute(array($playerName));
        $records = $stmt->fetchAll();
        
        echo json_encode(array(
            'success' => true,
            'records' => $records
        ));
        break;
    
    // 전체 랭킹 조회
    case 'get_ranking':
        $stmt = $pdo->query("
            SELECT 
                player_name,
                MAX(total_score) as best_score,
                COUNT(*) as play_count,
                AVG(total_score) as avg_score,
                MAX(created_at) as last_played
            FROM game_records
            GROUP BY player_name
            ORDER BY best_score DESC
            LIMIT 20
        ");
        $ranking = $stmt->fetchAll();
        
        echo json_encode(array(
            'success' => true,
            'ranking' => $ranking
        ));
        break;
    
    // 오늘의 기록 조회
    case 'get_today':
        $stmt = $pdo->query("
            SELECT * FROM game_records 
            WHERE DATE(created_at) = CURDATE()
            ORDER BY total_score DESC
            LIMIT 20
        ");
        $records = $stmt->fetchAll();
        
        echo json_encode(array(
            'success' => true,
            'records' => $records
        ));
        break;
    
    // 통계 조회
    case 'get_stats':
        $playerName = isset($_GET['player_name']) ? $_GET['player_name'] : '';
        
        if (empty($playerName)) {
            echo json_encode(array('success' => false, 'error' => '이름을 입력해주세요.'));
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(total_score) as best_score,
                AVG(total_score) as avg_score,
                MAX(stroop_score) as best_stroop,
                MAX(gonogo_score) as best_gonogo,
                MAX(nback_score) as best_nback,
                MAX(pal_score) as best_pal,
                MAX(ufov_score) as best_ufov,
                MAX(trail_score) as best_trail,
                MIN(created_at) as first_played,
                MAX(created_at) as last_played
            FROM game_records
            WHERE player_name = ?
        ");
        $stmt->execute(array($playerName));
        $stats = $stmt->fetch();
        
        echo json_encode(array(
            'success' => true,
            'stats' => $stats
        ));
        break;
    
    default:
        echo json_encode(array('success' => false, 'error' => '잘못된 요청입니다.'));
}
?>
