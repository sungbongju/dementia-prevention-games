-- dementia_games 데이터베이스 생성 및 테이블 설정
-- MySQL 5.7+ / MariaDB 10.2+
-- 
-- 🔄 v2 업데이트: 윷놀이 → 색상 패턴 기억 게임으로 변경

-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS dementia_games
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- 데이터베이스 선택
USE dementia_games;

-- 게임 기록 테이블
CREATE TABLE IF NOT EXISTS game_records (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '기록 ID',
    player_name VARCHAR(50) NOT NULL COMMENT '플레이어 이름',
    session_number INT NOT NULL COMMENT '회차 번호',
    hwatu_score INT DEFAULT 0 COMMENT '화투 짝맞추기 점수',
    pattern_score INT DEFAULT 0 COMMENT '색상 패턴 기억 점수',
    memory_score INT DEFAULT 0 COMMENT '숫자 기억하기 점수',
    proverb_score INT DEFAULT 0 COMMENT '속담 완성하기 점수',
    calc_score INT DEFAULT 0 COMMENT '산수 계산 점수',
    sequence_score INT DEFAULT 0 COMMENT '순서 맞추기 점수',
    total_score INT DEFAULT 0 COMMENT '총점',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '기록 생성 시간',
    
    -- 인덱스
    INDEX idx_player (player_name),
    INDEX idx_session (session_number),
    INDEX idx_created (created_at),
    INDEX idx_total_score (total_score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='치매예방 게임 기록 테이블 v2';

-- ============================================
-- 기존 테이블이 있는 경우 마이그레이션 (선택사항)
-- ============================================
-- 아래 쿼리는 기존 yut_score 컬럼이 있을 때만 실행하세요:
-- 
-- ALTER TABLE game_records 
-- CHANGE COLUMN yut_score pattern_score INT DEFAULT 0 COMMENT '색상 패턴 기억 점수';

-- ============================================
-- 샘플 데이터 (테스트용)
-- ============================================
-- INSERT INTO game_records (player_name, session_number, hwatu_score, pattern_score, memory_score, proverb_score, calc_score, sequence_score, total_score)
-- VALUES 
-- ('홍길동', 1, 85, 80, 80, 100, 80, 99, 524),
-- ('김영희', 1, 90, 60, 60, 80, 100, 66, 456),
-- ('이철수', 1, 75, 100, 100, 60, 80, 99, 514);

-- ============================================
-- 유용한 쿼리 예시
-- ============================================

-- 1. 전체 랭킹 조회 (최고점수 기준)
-- SELECT 
--     player_name,
--     MAX(total_score) as best_score,
--     COUNT(*) as play_count,
--     AVG(total_score) as avg_score
-- FROM game_records
-- GROUP BY player_name
-- ORDER BY best_score DESC;

-- 2. 특정 플레이어 기록 조회
-- SELECT * FROM game_records 
-- WHERE player_name = '홍길동' 
-- ORDER BY session_number DESC;

-- 3. 오늘의 기록 조회
-- SELECT * FROM game_records 
-- WHERE DATE(created_at) = CURDATE()
-- ORDER BY total_score DESC;

-- 4. 게임별 최고점수 보유자
-- SELECT 
--     (SELECT player_name FROM game_records ORDER BY hwatu_score DESC LIMIT 1) as hwatu_champion,
--     (SELECT player_name FROM game_records ORDER BY pattern_score DESC LIMIT 1) as pattern_champion,
--     (SELECT player_name FROM game_records ORDER BY memory_score DESC LIMIT 1) as memory_champion,
--     (SELECT player_name FROM game_records ORDER BY proverb_score DESC LIMIT 1) as proverb_champion,
--     (SELECT player_name FROM game_records ORDER BY calc_score DESC LIMIT 1) as calc_champion,
--     (SELECT player_name FROM game_records ORDER BY sequence_score DESC LIMIT 1) as sequence_champion;
