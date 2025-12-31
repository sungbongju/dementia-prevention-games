<?php
// api.php - 게임 기록 API
// 🔄 v2 업데이트: yut_score → pattern_score (색상 패턴 기억 게임)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    
    // 게임 기록 저장
    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || empty($data['player_name'])) {
            echo json_encode(['success' => false, 'error' => '이름을 입력해주세요.']);
            exit;
        }
        
        $playerName = trim($data['player_name']);
        
        // 해당 플레이어의 최대 회차 조회
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(session_number), 0) as max_session FROM game_records WHERE player_name = ?");
        $stmt->execute([$playerName]);
        $result = $stmt->fetch();
        $sessionNumber = $result['max_session'] + 1;
        
        // 기록 저장 (v2: pattern_score)
        $stmt = $pdo->prepare("
            INSERT INTO game_records 
            (player_name, session_number, hwatu_score, pattern_score, memory_score, proverb_score, calc_score, sequence_score, total_score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $hwatu = intval($data['hwatu_score'] ?? 0);
        $pattern = intval($data['pattern_score'] ?? 0);
        $memory = intval($data['memory_score'] ?? 0);
        $proverb = intval($data['proverb_score'] ?? 0);
        $calc = intval($data['calc_score'] ?? 0);
        $sequence = intval($data['sequence_score'] ?? 0);
        $total = $hwatu + $pattern + $memory + $proverb + $calc + $sequence;
        
        $stmt->execute([
            $playerName,
            $sessionNumber,
            $hwatu,
            $pattern,
            $memory,
            $proverb,
            $calc,
            $sequence,
            $total
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => '기록이 저장되었습니다!',
            'session_number' => $sessionNumber,
            'record_id' => $pdo->lastInsertId()
        ]);
        break;
    
    // 개인 기록 조회
    case 'get_records':
        $playerName = $_GET['player_name'] ?? '';
        
        if (empty($playerName)) {
            echo json_encode(['success' => false, 'error' => '이름을 입력해주세요.']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM game_records 
            WHERE player_name = ? 
            ORDER BY session_number DESC 
            LIMIT 20
        ");
        $stmt->execute([$playerName]);
        $records = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'records' => $records
        ]);
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
        
        echo json_encode([
            'success' => true,
            'ranking' => $ranking
        ]);
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
        
        echo json_encode([
            'success' => true,
            'records' => $records
        ]);
        break;
    
    // 통계 조회 (v2: pattern_score)
    case 'get_stats':
        $playerName = $_GET['player_name'] ?? '';
        
        if (empty($playerName)) {
            echo json_encode(['success' => false, 'error' => '이름을 입력해주세요.']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_games,
                MAX(total_score) as best_score,
                AVG(total_score) as avg_score,
                MAX(hwatu_score) as best_hwatu,
                MAX(pattern_score) as best_pattern,
                MAX(memory_score) as best_memory,
                MAX(proverb_score) as best_proverb,
                MAX(calc_score) as best_calc,
                MAX(sequence_score) as best_sequence,
                MIN(created_at) as first_played,
                MAX(created_at) as last_played
            FROM game_records
            WHERE player_name = ?
        ");
        $stmt->execute([$playerName]);
        $stats = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => '잘못된 요청입니다.']);
}
?>
