# 두뇌 건강을 위한 인지훈련 - 개발 문서

## 1. 프로젝트 개요

| 항목 | 내용 |
|------|------|
| 프로젝트명 | 두뇌 건강을 위한 인지훈련 (Dementia Prevention Cognitive Training Games) |
| 목적 | 고령자 대상 6종 인지훈련 게임 + AI 아바타 기반 치매 예방 웹 애플리케이션 |
| 대상 사용자 | 70대 이상 시니어 |
| 기술 스택 | Vanilla HTML/CSS/JS (프론트엔드), PHP 7.4+ / MySQL (백엔드), Next.js (아바타) |
| 배포 환경 | 게임: aiforalab.com 서버, 아바타: Netlify |
| 언어 | 모든 UI 텍스트 한국어 |

---

## 2. 시스템 아키텍처

```
+---------------------------+       +-------------------------+
|   Game Frontend           |       |   Avatar Frontend       |
|   (index.html)            |       |   (dementia-avatar)     |
|   서버: aiforalab.com     |  <->  |   서버: Netlify          |
|   Single HTML File        |       |   Next.js App           |
|   ~3,370 lines            |       |                         |
+----------+----------------+       +-------+-----------------+
           |  POST/GET                      |
           v                                v
+----------+----------------+       +-------+-----------------+
|   Backend API             |       |   External APIs         |
|   (api.php)               |       |   - HeyGen Avatar SDK   |
|   PHP 7.4+ / PDO          |       |   - OpenAI Chat API     |
+----------+----------------+       +-------------------------+
           |
           v
+----------+----------------+
|   Database                |
|   MySQL (dementia_games)  |
|   Table: game_records     |
+---------------------------+
```

### 2.1 통신 흐름

1. **게임 <-> 아바타**: `postMessage` API를 통한 iframe 간 통신
2. **게임 -> 서버**: `fetch()` API를 통한 JSON REST 통신
3. **아바타 -> HeyGen**: Interactive Avatar SDK (WebRTC)
4. **아바타 -> OpenAI**: Function Calling을 통한 DB 조회 및 대화

---

## 3. 파일 구조

```
dementia-prevention-games/
├── index.html                 # 메인 애플리케이션 (HTML + CSS + JS 통합, ~3,370줄)
├── api.php                    # 백엔드 REST API (5개 엔드포인트)
├── db_config.example.php      # DB 연결 설정 템플릿
├── db_config.php              # DB 연결 설정 (실제 사용, .gitignore)
├── setup_database.sql         # DB 스키마 정의 + 샘플 쿼리
├── db_migration.sh            # DB 컬럼 리네이밍 마이그레이션 스크립트
├── david_smc-ai-generated-8451407_1920.png  # OG 썸네일 이미지
├── image.png                  # 스크린샷 (참고용)
├── index_bak.html             # 백업 파일 (수정 금지)
├── index_html_6개 게임 참고용.html  # 참고용 원본
├── CLAUDE.md                  # AI 코딩 어시스턴트 가이드
├── DEVELOPMENT.md             # 이 문서
├── README.md                  # 프로젝트 소개
└── .gitignore                 # db_config.php 등 제외
```

---

## 4. index.html 상세 구조

단일 파일에 HTML, CSS, JavaScript가 모두 포함된 SPA(Single Page Application) 구조.

### 4.1 파일 구성 (라인 기준)

| 섹션 | 라인 범위 | 내용 |
|------|-----------|------|
| `<head>` 메타/OG | 1~16 | charset, viewport, OG 태그, Twitter Card, 폰트, Chart.js |
| CSS 변수 (`:root`) | 18~58 | 단청 테마 색상, 게임별 색상, glass 효과 변수 |
| CSS 공통 스타일 | 60~350 | 레이아웃, 헤더, 입력폼, 점수판, 게임카드, 버튼 |
| CSS 게임별 스타일 | 350~670 | 게임 아이콘 그라디언트, 카드 hover/glow, 모달 |
| CSS 애니메이션 | 670~1100 | @keyframes (fadeInUp, shimmer, scoreReveal 등 8개) |
| CSS 대시보드/랭킹 | 1100~1600 | 대시보드 카드, 차트, 기록 모달, 토스트 |
| CSS PIP 위젯 | 1600~1660 | 아바타 PIP 컨테이너 드래그/최소화/닫기 |
| HTML 메인 UI | 1660~1740 | 헤더, 이름 입력, 점수판, 게임 그리드 (6개 카드) |
| HTML 대시보드 모달 | 1740~1830 | 인지 대시보드, 인지 영역 차트, AI 인사이트 |
| HTML 게임 모달 6개 | 1830~2090 | 각 게임의 설명/플레이/결과 3단계 UI |
| HTML 랭킹/기록 모달 | 2090~2130 | 전체 랭킹, 오늘의 기록, 내 기록/통계 |
| JS 상태 관리 | 2130~2150 | gameState, dashboardData 초기화 |
| JS 공통 함수 | 2150~2450 | showToast, startGame, openGame, closeGame, 대시보드, 저장 등 |
| JS 게임 로직 | 2454~3300 | 6개 게임의 start/play/end 함수 |
| JS 이벤트/초기화 | 3300~3310 | 키보드 이벤트, 스코어보드 초기화 |
| HTML 아바타 PIP | 3310~3325 | iframe + 드래그 테두리 + 컨트롤 |
| JS PIP/음성명령 | 3325~3372 | PIP 드래그, 최소화, 음성명령 핸들러 |

### 4.2 CSS 설계

#### 디자인 시스템 - 단청(Dancheong) 전통 테마

```css
--hanji-cream: #F5F0E6;      /* 한지 배경색 */
--dancheong-red: #C73E3A;     /* 단청 빨강 (헤더) */
--dancheong-blue: #1B4965;    /* 단청 파랑 */
--dancheong-green: #2D5016;   /* 단청 초록 */
--dancheong-yellow: #E8B931;  /* 단청 노랑 */
--wood-brown: #8B4513;        /* 나무색 */
--gold-accent: #D4AF37;       /* 금색 테두리 */
```

#### 게임별 색상 체계

| 게임 | 메인 색상 | 변수명 | hex |
|------|----------|--------|-----|
| Stroop (글자 색깔 고르기) | 인디고 | `--stroop` | #6366F1 |
| Go/No-Go (눌러라 참아라) | 틸 | `--gonogo` | #14B8A6 |
| N-Back (앞의 숫자 맞추기) | 오렌지 | `--nback` | #F97316 |
| PAL (그림 자리 찾기) | 보라 | `--pal` | #8B5CF6 |
| UFOV (순간 포착 게임) | 시안 | `--ufov` | #06B6D4 |
| Trail Making (번호 순서대로 잇기) | 핑크 | `--trail` | #EC4899 |

각 게임에 `-dark`, `-glow`, `-light` 변형 색상도 정의되어 있음.

#### 시니어 친화 설계 원칙

- 폰트 크기: 최소 1.3rem (게임 설명), 제목 1.6rem+
- 버튼 패딩: 25px+ (터치 영역 확보)
- 고대비: 어두운 텍스트 + 밝은 배경
- SVG 배경 패턴: 한지 질감 모방

#### CSS 애니메이션 (@keyframes)

| 애니메이션 | 용도 |
|-----------|------|
| `fadeInUp` | 모달 진입 |
| `fadeInScale` | 게임 카드 등장 |
| `shimmer` | 카드 hover 시 빛 흐름 |
| `scoreReveal` | 결과 점수 표시 |
| `gaugeGrow` | 결과 게이지 바 채움 |
| `glassShine` | 글래스모피즘 빛 효과 |
| `pulseGlow` | 카드 완료 시 반짝임 |
| `slideDown` | 토스트 알림 |

---

## 5. 6종 인지훈련 게임 상세

### 5.1 게임 매핑 테이블

| # | 내부 키 | 표시 이름 | 인지 훈련 영역 | DB 컬럼 | 만점 |
|---|---------|----------|--------------|---------|------|
| 1 | `stroop` | 글자 색깔 고르기 | 선택적 주의력 (Selective Attention) | `stroop_score` | 100 |
| 2 | `gonogo` | 눌러라 참아라 | 억제 통제력 (Inhibitory Control) | `gonogo_score` | 100 |
| 3 | `nback` | 앞의 숫자 맞추기 | 작업기억 (Working Memory) | `nback_score` | 100 |
| 4 | `pal` | 그림 자리 찾기 | 시공간 기억 (Visuospatial Memory) | `pal_score` | 100 |
| 5 | `ufov` | 순간 포착 게임 | 처리 속도 (Processing Speed) | `ufov_score` | 100 |
| 6 | `trail` | 번호 순서대로 잇기 | 실행 기능 (Executive Function) | `trail_score` | 100 |

### 5.2 Game 1: Stroop Task (글자 색깔 고르기)

**과학적 근거**: Stroop 간섭 효과 (1935, J.R. Stroop)

**게임 규칙**:
- 색깔 단어(빨강, 파랑, 초록, 노랑)가 다른 색으로 표시됨
- 글자의 의미가 아닌 **글자의 색깔**을 선택
- 5라운드 x 4문제 = 총 20문제
- 라운드별 제한시간: 5초 -> 4초 -> 3.5초 -> 3초 -> 2.5초

**채점 방식**:
- 정답 시: 기본 3점 + 반응속도 보너스 (최대 2점)
- 오답/시간초과: 0점
- 총점 = min(100, 합계)

**주요 변수**: `STROOP_COLORS` (4색), `STROOP_TIME_LIMITS` (5단계)

### 5.3 Game 2: Go/No-Go Task (눌러라 참아라)

**과학적 근거**: Go/No-Go 패러다임 - 억제 통제력 측정

**게임 규칙**:
- 초록 원(Go, 21회): 빠르게 TAP 버튼 누르기
- 빨간 X(No-Go, 9회): 누르지 않기 (억제)
- 총 30회 시행, 무작위 순서
- 자극 간 간격(ISI): 500~1500ms 랜덤
- 자극 표시 시간: 1500ms

**채점 방식**:
- Hit (Go 정확 반응): +3점
- Correct Rejection (No-Go 정확 억제): +4점
- Miss (Go 미반응): 0점
- False Alarm (No-Go 오반응): -2점
- 최종 점수 = (원점수 / 99) * 100 (0~100 정규화)

**결과 지표**: Hit율, 정확한 억제율, Miss 수, 오경보(FA) 수

### 5.4 Game 3: N-Back Task (앞의 숫자 맞추기)

**과학적 근거**: N-Back 패러다임 - 작업기억 용량 측정

**게임 규칙**:
- 숫자가 하나씩 화면에 나타남
- N번 전에 본 숫자와 같으면 "같음", 다르면 "다름" 선택
- 3단계 진행: 1-Back(15회) -> 2-Back(15회) -> 3-Back(10회)

**채점 방식**:
- 레벨별 가중치: 1-Back(2.0), 2-Back(2.5), 3-Back(3.2)
- 정답 시 가중치 점수 부여
- 총점 = min(100, 합계)

**주요 변수**: `NBACK_LEVELS` (3단계 설정)

### 5.5 Game 4: PAL - Paired Associates Learning (그림 자리 찾기)

**과학적 근거**: CANTAB PAL - 시공간 연합 기억 측정

**게임 규칙**:
- 상자가 하나씩 열리며 기호(★, ♥, ◆, ● 등) 표시
- 학습 후 기호가 주어지면 올바른 상자 위치 선택
- 3라운드: 4칸(2x2) -> 6칸(3x3) -> 6칸(3x3)
- 각 라운드 최대 3회 시도

**채점 방식**:
- 라운드별 배점: 40점 / 35점 / 25점
- 시도 횟수에 따라 감점: 1회차 100%, 2회차 50%, 3회차 25%
- 총점 = min(100, 합계)

**주요 변수**: `PAL_ICONS` (9개 기호), `PAL_ROUNDS` (3라운드 설정)

### 5.6 Game 5: UFOV - Useful Field of View (순간 포착 게임)

**과학적 근거**: UFOV Test - 시각적 처리 속도 및 분할 주의력 측정

**게임 규칙**:
- Task 1: 중앙 도형 식별 (6회)
- Task 2: 중앙 도형 + 주변 방향 식별 (6회)
- Task 3: 중앙 도형 + 방해 자극 속 주변 방향 식별 (6회)
- 총 18회, 짧은 노출 시간

**채점 방식**:
- Task별 배점: Task1(30점), Task2(35점), Task3(35점)
- 정답 시 해당 Task 배점/시행수 부여
- 총점 = min(100, 합계)

**주요 변수**: `UFOV_TASKS` (3과제), `UFOV_SHAPES` (도형), `UFOV_DIRS` (8방향)

### 5.7 Game 6: Trail Making Test (번호 순서대로 잇기)

**과학적 근거**: TMT (Trail Making Test) - 실행 기능, 인지 유연성 측정

**게임 규칙**:
- Part A: 1->2->3->...->10 숫자 순서 연결
- Part B: 1->가->2->나->3->다 숫자/글자 교대 연결
- 빠르고 정확하게 터치

**채점 방식**:
- Part A: 50점 만점 (시간 기반 점수 + 오류 감점)
- Part B: 50점 만점 (동일 방식)
- 총점 = Part A + Part B (최대 100점)

---

## 6. JavaScript 상태 관리

### 6.1 gameState 객체

```javascript
let gameState = {
    playerName: '',           // 플레이어 이름
    sessionNumber: 0,         // 현재 회차
    totalScore: 0,            // 6게임 총점
    completedGames: [],       // 완료된 게임 키 배열
    saved: false,             // 저장 여부
    stroop:  { score: 0, completed: false },
    gonogo:  { score: 0, completed: false },
    nback:   { score: 0, completed: false },
    pal:     { score: 0, completed: false },
    ufov:    { score: 0, completed: false },
    trail:   { score: 0, completed: false }
};
```

### 6.2 사용자 흐름

```
이름 입력 -> [게임 시작] 버튼 -> 게임 카드 활성화
    -> 6개 게임 중 원하는 것 선택 (순서 자유)
        -> 설명 화면 (instruction)
        -> 게임 플레이 (playing)
        -> 결과 화면 (result)
        -> 모달 닫기 -> 카드에 '완료' 표시
    -> 6개 모두 완료 시 [기록 저장하기] 버튼 활성화
    -> 저장 -> DB에 기록
```

### 6.3 대시보드 (dashboardData)

```javascript
let dashboardData = {
    history: [],               // 과거 기록 배열 (API에서 조회)
    currentCognitive: {        // 현재 세션 인지 영역별 점수 (%)
        memory: 0,             // 작업기억 (N-Back)
        attention: 0,          // 억제 통제 (Go/No-Go)
        language: 0,           // 주의 집중 (Stroop + UFOV 평균)
        calculation: 0,        // 시공간 기억 (PAL)
        reasoning: 0           // 실행 기능 (Trail Making)
    }
};
```

인지 영역 -> 게임 매핑:
- `memory` (작업기억) = N-Back 점수
- `attention` (억제 통제) = Go/No-Go 점수
- `language` (주의 집중) = (Stroop + UFOV) / 2
- `calculation` (시공간 기억) = PAL 점수
- `reasoning` (실행 기능) = Trail Making 점수

---

## 7. 백엔드 API (api.php)

### 7.1 엔드포인트

| Action | Method | 설명 | 파라미터 |
|--------|--------|------|---------|
| `save` | POST | 게임 기록 저장 | JSON body: player_name, stroop_score, gonogo_score, nback_score, pal_score, ufov_score, trail_score |
| `get_records` | GET | 개인 기록 조회 (최근 20건) | ?player_name=이름 |
| `get_ranking` | GET | 전체 랭킹 (최고점수 Top 20) | 없음 |
| `get_today` | GET | 오늘의 기록 (Top 20) | 없음 |
| `get_stats` | GET | 개인 통계 (총 플레이수, 최고/평균 점수, 게임별 최고) | ?player_name=이름 |

### 7.2 API URL

```
const API_URL = 'api.php';  // 같은 디렉토리에서 상대 경로
```

### 7.3 응답 형식

```json
{
    "success": true,
    "message": "기록이 저장되었습니다!",
    "session_number": 3,
    "record_id": 42
}
```

---

## 8. 데이터베이스

### 8.1 테이블 스키마 (game_records)

| 컬럼명 | 타입 | 설명 |
|--------|------|------|
| `id` | INT AUTO_INCREMENT | PK |
| `player_name` | VARCHAR(50) | 플레이어 이름 |
| `session_number` | INT | 회차 번호 (자동 증가) |
| `stroop_score` | INT DEFAULT 0 | Stroop 점수 (0~100) |
| `gonogo_score` | INT DEFAULT 0 | Go/No-Go 점수 (0~100) |
| `nback_score` | INT DEFAULT 0 | N-Back 점수 (0~100) |
| `pal_score` | INT DEFAULT 0 | PAL 점수 (0~100) |
| `ufov_score` | INT DEFAULT 0 | UFOV 점수 (0~100) |
| `trail_score` | INT DEFAULT 0 | Trail Making 점수 (0~100) |
| `total_score` | INT DEFAULT 0 | 총점 (0~600) |
| `created_at` | TIMESTAMP | 기록 생성 시간 |

### 8.2 인덱스

- `idx_player` (player_name) - 개인 기록 조회
- `idx_session` (session_number) - 회차 조회
- `idx_created` (created_at) - 날짜별 조회
- `idx_total_score` (total_score DESC) - 랭킹 정렬

### 8.3 컬럼명 변경 이력

```
v1 (원래)     -> v2 (게임 변경)  -> v3 (현재, 레퍼런스명)
hwatu_score   -> hwatu_score     -> stroop_score
yut_score     -> pattern_score   -> gonogo_score
memory_score  -> memory_score    -> nback_score
proverb_score -> proverb_score   -> pal_score
calc_score    -> calc_score      -> ufov_score
sequence_score-> sequence_score  -> trail_score
```

---

## 9. AI 아바타 연동

### 9.1 아바타 구성

| 항목 | 값 |
|------|-----|
| 플랫폼 | HeyGen Interactive Avatar SDK |
| 아바타 ID | e2eb35c947644f09820aa3a4f9c15488 |
| 프론트엔드 | Next.js (dementia-avatar 레포) |
| 배포 | Netlify (https://dementia-avatar.netlify.app/) |
| iframe | index.html 하단 PIP 위젯으로 삽입 |

### 9.2 postMessage 이벤트

#### 게임 -> 아바타 (index.html -> iframe)

| type | 용도 | payload |
|------|------|---------|
| `START_AVATAR` | 아바타 세션 시작 | `{ name, stats }` |
| `GAME_COMPLETE` | 게임 완료 알림 | `{ game, gameName, score, maxScore, playerName, completedCount, totalGames }` |
| `EXPLAIN_GAME` | 게임 설명 요청 | `{ game }` |
| `EXPLAIN_DASHBOARD` | 대시보드 설명 요청 | `{ cognitive }` |
| `RESET_AVATAR` | 아바타 리셋 | - |

#### 아바타 -> 게임 (iframe -> index.html)

| type | action | 용도 |
|------|--------|------|
| `VOICE_COMMAND` | `START_GAME_STROOP` | 스트룹 게임 시작 |
| `VOICE_COMMAND` | `START_GAME_GONOGO` | Go/No-Go 게임 시작 |
| `VOICE_COMMAND` | `START_GAME_NBACK` | N-Back 게임 시작 |
| `VOICE_COMMAND` | `START_GAME_PAL` | PAL 게임 시작 |
| `VOICE_COMMAND` | `START_GAME_UFOV` | UFOV 게임 시작 |
| `VOICE_COMMAND` | `START_GAME_TRAIL` | Trail Making 게임 시작 |
| `VOICE_COMMAND` | `SHOW_MY_RECORDS` | 내 기록 보기 |
| `VOICE_COMMAND` | `SHOW_DASHBOARD` | 대시보드 열기 |
| `VOICE_COMMAND` | `SHOW_RANKING` | 랭킹 보기 |
| `VOICE_COMMAND` | `SAVE_SCORE` | 점수 저장 |
| `VOICE_COMMAND` | `CLOSE_MODAL` | 모달 닫기 |

### 9.3 PIP 위젯

- 드래그 가능 (마우스/터치)
- 최소화/닫기/열기 토글
- 우하단 기본 위치

---

## 10. 외부 의존성

| 라이브러리 | 버전 | 용도 | 로드 방식 |
|-----------|------|------|----------|
| Chart.js | latest (CDN) | 대시보드 인지 영역 차트 | `<script>` 태그 |
| Noto Serif KR | Google Fonts | 제목용 세리프 폰트 | `<link>` 태그 |
| Noto Sans KR | Google Fonts | 본문용 산세리프 폰트 | `<link>` 태그 |

빌드 도구 없음. 번들러 없음. 프레임워크 없음. 순수 HTML/CSS/JS.

---

## 11. 로컬 개발 환경 설정

```bash
# 1. DB 설정
cp db_config.example.php db_config.php
# db_config.php에서 DB 호스트/유저/비밀번호 수정

# 2. DB 생성
mysql -u root -p < setup_database.sql

# 3. 웹 서버 실행
php -S localhost:8000
# 또는 Python으로:
python -m http.server 8000

# 4. 브라우저에서 접속
# http://localhost:8000/index.html
# (Python 서버 사용 시 API는 동작하지 않음 - PHP 서버 필요)
```

---

## 12. 서버 배포

### 12.1 게임 서버 (aiforalab.com)

```bash
ssh -p 10022 user2@106.247.236.2
cd /path/to/dementia-prevention-games-v2
sudo git pull origin main
```

### 12.2 아바타 서버 (Netlify)

- GitHub 레포: sungbongju/dementia-avatar
- 자동 배포: main 브랜치 push 시 자동 빌드
- 환경 변수 (Netlify Dashboard):
  - `HEYGEN_API_KEY`: HeyGen API 키
  - `OPENAI_API_KEY`: OpenAI API 키
  - `NEXT_PUBLIC_BASE_API_URL`: `https://api.heygen.com`
  - `DB_API_URL`: PHP API 서버 URL (예: `https://aiforalab.com/dementia-prevention-games-v2/api.php`)

---

## 13. OG 메타태그 (URL 공유 미리보기)

```html
<meta property="og:title" content="두뇌 건강을 위한 인지훈련">
<meta property="og:description" content="6가지 과학적 인지 훈련 게임으로 두뇌 건강을 지키세요!">
<meta property="og:image" content="https://aiforalab.com/dementia-prevention-games-v2/david_smc-ai-generated-8451407_1920.png">
<meta property="og:type" content="website">
```

카카오톡 캐시 초기화: https://developers.kakao.com/tool/debugger/sharing

---

## 14. 네이밍 컨벤션 요약

### 14.1 키 네이밍 규칙 (전 레이어 통일)

```
stroop  = Stroop Task        = 글자 색깔 고르기   = 선택적 주의력
gonogo  = Go/No-Go Task      = 눌러라 참아라     = 억제 통제력
nback   = N-Back Task        = 앞의 숫자 맞추기   = 작업기억
pal     = PAL                = 그림 자리 찾기     = 시공간 기억
ufov    = UFOV               = 순간 포착 게임     = 처리 속도
trail   = Trail Making Test  = 번호 순서대로 잇기  = 실행 기능
```

### 14.2 적용 범위

- DB 컬럼: `{key}_score` (예: `stroop_score`)
- JS gameState: `gameState.{key}` (예: `gameState.stroop`)
- HTML ID: `modal-{key}`, `card-{key}`, `dash-{key}`, `{key}-score`
- CSS 클래스: `.game-icon.{key}`, `.modal-header.{key}`
- CSS 변수: `--{key}`, `--{key}-dark`, `--{key}-glow`, `--{key}-light`
- API 파라미터: `{key}_score` (예: `stroop_score`)
- 아바타 통신: `game: '{key}'` (예: `game: 'stroop'`)

---

## 15. 변경 이력

### v2.5 (2026-03-05)

#### N-Back 게임 리뉴얼
- **3단계 레벨 시스템**: 1-Back(11시행, 30점) → 2-Back(7시행, 35점) → 3-Back(6시행, 35점)
- **레벨 전환 화면 추가**: 각 레벨 시작 전 설명 + "준비 완료! 시작하기" 버튼
- **시간제한 제거**: 같음/다름 선택이므로 시간 압박 없이 진행
- **정확도 기반 채점**: 레벨별 (정답 수 / 판단 가능 시행 수) × 배점
- **아바타 레벨 설명**: 레벨 전환 시 아바타가 TTS로 다음 레벨 규칙 설명 (`NBACK_LEVEL` postMessage)

#### 아바타 연동 개선
- **그리팅 타이밍 수정**: 게임 설명이 먼저 시작된 경우 인사말 취소 (cancellable timer)
- **아바타 세션 종료**: X 버튼 클릭 시 `STOP_AVATAR` postMessage 전송하여 세션 종료
- **토글 아이콘**: 로봇 이모지(🤖) → 친근한 얼굴 SVG 아이콘으로 교체
- **격려 메시지 수정**: 공통 메시지에서 특정 인지영역 언급 제거 (예: "기억력이 좋으시네요" → "정말 잘하시네요")

#### TTS 발음 최적화
- **화면 표시 vs 아바타 읽기 분리**:
  - `NBACK_EXPLANATIONS`: 화면용 숫자 (3 → 7 → 3)
  - `NBACK_EXPLANATIONS_AVATAR`: TTS용 한글 (삼 → 칠 → 삼)
- **숫자 뒤 조사 띄어쓰기**: "3이" → "3 이", "5가" → "5 가" (자연스러운 읽기)
- **아바타 게임 설명 한글화**: nback/trail 설명에서 숫자를 한글 텍스트로 변환

#### API 버그 수정
- **기록 저장 실패 수정**: `action`을 POST body가 아닌 URL 쿼리 파라미터로 전달 (`api.php?action=save`)
- PHP의 `$_GET['action']`과 호환되도록 fetch URL 변경

#### DB 컬럼 마이그레이션
- 레거시 컬럼명을 학술 레퍼런스명으로 통일:
  ```sql
  hwatu_score    → stroop_score
  yut_score      → gonogo_score
  memory_score   → nback_score
  proverb_score  → pal_score
  calc_score     → ufov_score
  sequence_score → trail_score
  ```

#### 기타
- **OG 메타태그 추가**: URL 공유 시 썸네일 이미지 표시
- **DB_API_URL 환경변수**: Netlify에 추가 필요 (아바타 → DB 조회용)
