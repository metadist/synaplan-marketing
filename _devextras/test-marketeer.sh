#!/usr/bin/env bash
# Marketeer Plugin MVP — Integration Test
# Usage: ./test-marketeer.sh <api_key> [base_url] [user_id]

API_KEY="${1:?Usage: $0 <api_key> [base_url] [user_id]}"
BASE="${2:-http://localhost:8000}"
USER_ID="${3:-1}"
URL="$BASE/api/v1/user/$USER_ID/plugins/marketeer"
PASS=0
FAIL=0
SLUG="mvptest$$"

call() {
  local method="$1" path="$2" body="${3:-}"
  if [ -n "$body" ]; then
    curl -s -X "$method" -H "X-API-Key: $API_KEY" -H "Content-Type: application/json" "$URL$path" -d "$body" 2>/dev/null || echo '{"success":false,"error":"curl_failed"}'
  else
    curl -s -X "$method" -H "X-API-Key: $API_KEY" "$URL$path" 2>/dev/null || echo '{"success":false,"error":"curl_failed"}'
  fi
}

check() {
  local name="$1" json="$2"
  if echo "$json" | python3 -c "import sys,json; d=json.load(sys.stdin); assert d.get('success')==True" 2>/dev/null; then
    echo "  PASS  $name"
    PASS=$((PASS + 1))
  else
    echo "  FAIL  $name"
    echo "        $(echo "$json" | head -c 200)"
    FAIL=$((FAIL + 1))
  fi
}

check_false() {
  local name="$1" json="$2"
  if echo "$json" | python3 -c "import sys,json; d=json.load(sys.stdin); assert d.get('success')==False" 2>/dev/null; then
    echo "  PASS  $name"
    PASS=$((PASS + 1))
  else
    echo "  FAIL  $name"
    FAIL=$((FAIL + 1))
  fi
}

echo "=== Marketeer MVP Test Suite ==="
echo "    Target: $URL"
echo ""

R=$(call GET /setup-check)
check "Setup check" "$R"

R=$(call POST /setup)
check "Seed defaults" "$R"

R=$(call POST /campaigns "{\"slug\":\"$SLUG\",\"title\":\"Test Campaign\",\"topic\":\"Automated test\",\"languages\":[\"en\"],\"platforms\":[\"google\",\"linkedin\"],\"ctas\":[{\"type\":\"register\",\"label\":\"Sign Up\",\"url\":\"https://example.com\"}]}")
check "Create campaign" "$R"

R=$(call GET /campaigns)
check "List campaigns" "$R"

R=$(call GET "/campaigns/$SLUG")
check "Get campaign" "$R"

R=$(call PUT "/campaigns/$SLUG" "{\"status\":\"active\",\"target_audience\":\"Testers\"}")
check "Update campaign" "$R"

R=$(call POST "/campaigns/$SLUG/ads-campaigns" "{\"campaign_name\":\"Test Ads\",\"language\":\"en\",\"ad_groups\":[{\"name\":\"Test Group\",\"keywords\":[{\"keyword\":\"test keyword\",\"match_type\":\"phrase\"}]}]}")
check "Create ads campaign" "$R"

R=$(call GET "/campaigns/$SLUG/ads-campaigns")
check "List ads campaigns" "$R"

R=$(call GET "/campaigns/$SLUG/ad-copy")
check "List ad copy" "$R"

R=$(call GET "/campaigns/$SLUG/compliance")
check "Compliance check" "$R"

R=$(call GET "/compliance/cookie-snippet?language=en")
check "Cookie snippet" "$R"

R=$(call GET /dashboard)
check "Dashboard" "$R"

R=$(call DELETE "/campaigns/$SLUG")
check "Delete campaign" "$R"

R=$(call GET "/campaigns/$SLUG")
check_false "Verify deleted (404)" "$R"

echo ""
echo "=== Results: $PASS passed, $FAIL failed ==="
if [ "$FAIL" -eq 0 ]; then
  echo "All tests passed."
  exit 0
else
  echo "FAILURES DETECTED."
  exit 1
fi
