<?php
// db_config.php - 데이터베이스 연결 설정
// 
// 사용 방법:
// 1. 이 파일을 복사하여 db_config.php로 저장
// 2. 아래 정보를 실제 DB 정보로 수정
//
// cp db_config.example.php db_config.php
// nano db_config.php
//
// 🔄 v2 업데이트: yut_score → pattern_score

$host = 'localhost';           // DB 서버 주소
$username = 'your_username';   // DB 사용자명
$password = 'your_password';   // DB 비밀번호
$database = 'dementia_games';  // DB 이름

// PDO 연결
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // 데이터베이스가 없으면 생성
    try {
        $pdo = new PDO(
            "mysql:host=$host;charset=utf8mb4",
            $username,
            $password
        );
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$database`");
        
        // 테이블 생성 (v2: pattern_score)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS game_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                player_name VARCHAR(50) NOT NULL,
                session_number INT NOT NULL,
                hwatu_score INT DEFAULT 0,
                pattern_score INT DEFAULT 0,
                memory_score INT DEFAULT 0,
                proverb_score INT DEFAULT 0,
                calc_score INT DEFAULT 0,
                sequence_score INT DEFAULT 0,
                total_score INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_player (player_name),
                INDEX idx_session (session_number),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
    } catch (PDOException $e2) {
        die(json_encode(['error' => '데이터베이스 연결 실패: ' . $e2->getMessage()]));
    }
}
?>
