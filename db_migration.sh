#!/bin/bash
# DB 컬럼명 변경 스크립트
# dementia_games DB의 game_records 테이블만 변경합니다.
# 기존 데이터는 유지됩니다.

mysql -u user2 -p'user2!!' dementia_games -e "
ALTER TABLE game_records
  CHANGE COLUMN hwatu_score stroop_score INT DEFAULT 0,
  CHANGE COLUMN pattern_score gonogo_score INT DEFAULT 0,
  CHANGE COLUMN memory_score nback_score INT DEFAULT 0,
  CHANGE COLUMN proverb_score pal_score INT DEFAULT 0,
  CHANGE COLUMN calc_score ufov_score INT DEFAULT 0,
  CHANGE COLUMN sequence_score trail_score INT DEFAULT 0;
"

echo "완료! 변경된 테이블 확인:"
mysql -u user2 -p'user2!!' dementia_games -e "DESCRIBE game_records;"
