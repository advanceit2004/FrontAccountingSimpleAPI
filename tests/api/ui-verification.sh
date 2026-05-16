#!/usr/bin/env bash
set -euo pipefail

BASE_URL=${BASE_URL:-http://localhost:8080/modules/api/public/index.php/v1}
API_COMPANY=${API_COMPANY:-0}
API_USER=${API_USER:-api_test}
API_PASSWORD=${API_PASSWORD:-ApiTest!2026}
RUN_ID=${RUN_ID:-UI$(date +%m%d%H%M%S)}
DOC_DATE=${DOC_DATE:-2026-05-16}
LOCATION=${LOCATION:-MEL}
TMP_DIR=${TMP_DIR:-/tmp/frontaccounting-api-ui-verification-$RUN_ID}
mkdir -p "$TMP_DIR"

fail() { echo "FAIL: $*" >&2; exit 1; }
info() { echo "== $* =="; }

request() {
  local method=$1 endpoint=$2 body=${3:-} output=$TMP_DIR/response.json code
  if [[ -n "$body" ]]; then
    code=$(curl -sS -o "$output" -w '%{http_code}' -X "$method" \
      -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
      -d "$body" "$BASE_URL/$endpoint")
  else
    code=$(curl -sS -o "$output" -w '%{http_code}' -X "$method" \
      -H "Authorization: Bearer $TOKEN" "$BASE_URL/$endpoint")
  fi
  echo "$code $method /$endpoint"
  [[ $code =~ ^2 ]] || { cat "$output" >&2; fail "$method /$endpoint returned HTTP $code"; }
}

json_get() {
  python3 -c "import json,sys; data=json.load(open(sys.argv[1])); cur=data
for part in sys.argv[2].split('.'):
    cur=cur[part] if isinstance(cur,dict) else cur[int(part)]
print(cur)" "$1" "$2"
}

info "login"
LOGIN_BODY=$(printf '{"company":%s,"username":"%s","password":"%s"}' "$API_COMPANY" "$API_USER" "$API_PASSWORD")
LOGIN_CODE=$(curl -sS -o "$TMP_DIR/login.json" -w '%{http_code}' -H 'Content-Type: application/json' -d "$LOGIN_BODY" "$BASE_URL/auth/login")
[[ "$LOGIN_CODE" == 200 ]] || { cat "$TMP_DIR/login.json" >&2; fail "login returned HTTP $LOGIN_CODE"; }
TOKEN=$(json_get "$TMP_DIR/login.json" data.accessToken)

STOCK_ID="IT$RUN_ID"
CUSTOMER_REF="C$RUN_ID"
SUPPLIER_REF="S$RUN_ID"

CUSTOMER_NAME="API UI Verify Customer $RUN_ID"
SUPPLIER_NAME="API UI Verify Supplier $RUN_ID"
ITEM_NAME="API UI Verify Item $RUN_ID"

info "create customer"
CUSTOMER_BODY=$(cat <<JSON
{"name":"$CUSTOMER_NAME","reference":"$CUSTOMER_REF","currency":"AUD","address":"123 API Verification Street","creditStatus":1,"paymentTerms":4,"salesType":1,"creditLimit":5000,"notes":"Created by API UI verification test $RUN_ID"}
JSON
)
request POST customers "$CUSTOMER_BODY"
CUSTOMER_ID=$(json_get "$TMP_DIR/response.json" data.id)

info "create supplier"
SUPPLIER_BODY=$(cat <<JSON
{"name":"$SUPPLIER_NAME","reference":"$SUPPLIER_REF","currency":"AUD","payableAccount":"20000","purchaseAccount":"6550","paymentDiscountAccount":"7040","taxGroupId":1,"notes":"Created by API UI verification test $RUN_ID"}
JSON
)
request POST suppliers "$SUPPLIER_BODY"
SUPPLIER_ID=$(json_get "$TMP_DIR/response.json" data.id)

info "create stock item"
ITEM_BODY=$(cat <<JSON
{"stockId":"$STOCK_ID","description":"$ITEM_NAME","categoryId":1,"taxTypeId":1,"units":"","mbFlag":"B","salesAccount":"4050","inventoryAccount":"1200","cogsAccount":"6920","adjustmentAccount":"1205","wipAccount":"6910"}
JSON
)
request POST items "$ITEM_BODY"

info "create purchase order"
PO_REF="UI-PO-$RUN_ID"
PO_BODY=$(cat <<JSON
{"supplierId":$SUPPLIER_ID,"date":"$DOC_DATE","deliveryDate":"$DOC_DATE","reference":"$PO_REF","supplierReference":"Supplier ref $RUN_ID","location":"$LOCATION","memo":"API UI verification purchase order $RUN_ID","lines":[{"stockId":"$STOCK_ID","quantity":5,"price":12.50,"description":"$ITEM_NAME"}]}
JSON
)
request POST purchase/orders "$PO_BODY"
PO_ID=$(json_get "$TMP_DIR/response.json" data.id)

info "create purchase receipt"
GRN_REF="UI-GRN-$RUN_ID"
GRN_BODY=$(cat <<JSON
{"orderId":$PO_ID,"date":"$DOC_DATE","reference":"$GRN_REF","location":"$LOCATION","memo":"API UI verification purchase receipt $RUN_ID","lines":[{"stockId":"$STOCK_ID","quantity":5}]}
JSON
)
request POST purchase/receipts "$GRN_BODY"
GRN_ID=$(json_get "$TMP_DIR/response.json" data.id)
request GET "purchase/receipts/$GRN_ID"

info "create supplier invoice"
SUPP_INV_REF="UI-SI-$RUN_ID"
SUPP_INV_BODY=$(cat <<JSON
{"receiptId":$GRN_ID,"date":"$DOC_DATE","dueDate":"$DOC_DATE","reference":"$SUPP_INV_REF","supplierReference":"Supplier invoice $RUN_ID","memo":"API UI verification supplier invoice $RUN_ID","lines":[{"stockId":"$STOCK_ID","quantity":5,"price":12.50}]}
JSON
)
request POST purchase/invoices "$SUPP_INV_BODY"
SUPP_INV_ID=$(json_get "$TMP_DIR/response.json" data.id)
request GET "purchase/invoices/$SUPP_INV_ID"

info "create sales order"
SO_REF="UI-SO-$RUN_ID"
SO_BODY=$(cat <<JSON
{"customerId":$CUSTOMER_ID,"date":"$DOC_DATE","deliveryDate":"$DOC_DATE","reference":"$SO_REF","customerReference":"Customer ref $RUN_ID","location":"$LOCATION","memo":"API UI verification sales order $RUN_ID","lines":[{"stockId":"$STOCK_ID","quantity":2,"price":25.00,"discount":0,"description":"$ITEM_NAME"}]}
JSON
)
request POST sales/orders "$SO_BODY"
SO_ID=$(json_get "$TMP_DIR/response.json" data.id)

info "create sales delivery"
DN_REF="UI-DN-$RUN_ID"
DN_BODY=$(cat <<JSON
{"orderId":$SO_ID,"date":"$DOC_DATE","deliveryDate":"$DOC_DATE","reference":"$DN_REF","location":"$LOCATION","memo":"API UI verification sales delivery $RUN_ID","lines":[{"stockId":"$STOCK_ID","quantity":2}]}
JSON
)
request POST sales/deliveries "$DN_BODY"
DN_ID=$(json_get "$TMP_DIR/response.json" data.id)
request GET "sales/deliveries/$DN_ID"

info "create sales invoice"
INV_REF="UI-INV-$RUN_ID"
INV_BODY=$(cat <<JSON
{"deliveryId":$DN_ID,"date":"$DOC_DATE","dueDate":"$DOC_DATE","reference":"$INV_REF","memo":"API UI verification sales invoice $RUN_ID","lines":[{"stockId":"$STOCK_ID","quantity":2}]}
JSON
)
request POST sales/invoices "$INV_BODY"
INV_ID=$(json_get "$TMP_DIR/response.json" data.id)
request GET "sales/invoices/$INV_ID"

info "create customer payment"
BANK_ID=$(python3 - <<'PY' "$BASE_URL" "$TOKEN" "$TMP_DIR"
import json, subprocess, sys
base, token, tmp = sys.argv[1:]
out = subprocess.check_output(['curl','-sS','-H',f'Authorization: Bearer {token}',f'{base}/bank-accounts'])
p = json.loads(out)
print(p['data'][0]['id'])
PY
)
CP_REF="UI-CP-$RUN_ID"
CP_BODY=$(cat <<JSON
{"customerId":$CUSTOMER_ID,"branchId":0,"bankAccount":$BANK_ID,"date":"$DOC_DATE","reference":"$CP_REF","amount":55.00,"memo":"API UI verification customer payment $RUN_ID"}
JSON
)
request POST customer-payments "$CP_BODY"
CP_ID=$(json_get "$TMP_DIR/response.json" data.id)

info "create supplier payment"
SP_REF="UI-SP-$RUN_ID"
SP_BODY=$(cat <<JSON
{"supplierId":$SUPPLIER_ID,"bankAccount":$BANK_ID,"date":"$DOC_DATE","reference":"$SP_REF","amount":68.75,"memo":"API UI verification supplier payment $RUN_ID"}
JSON
)
request POST supplier-payments "$SP_BODY"
SP_ID=$(json_get "$TMP_DIR/response.json" data.id)

info "create journal entry"
JOURNAL_REF="UI-JNL-$RUN_ID"
JOURNAL_BODY=$(cat <<JSON
{"date":"$DOC_DATE","memo":"API UI verification journal $RUN_ID","reference":"$JOURNAL_REF","lines":[{"account":"1200","amount":10,"memo":"API UI verification debit $RUN_ID"},{"account":"4050","amount":-10,"memo":"API UI verification credit $RUN_ID"}]}
JSON
)
request POST journal-entries "$JOURNAL_BODY"
JOURNAL_ID=$(json_get "$TMP_DIR/response.json" data.id)
request GET "journal-entries/$JOURNAL_ID"

SUMMARY="$TMP_DIR/ui-verification-summary.md"
cat > "$SUMMARY" <<EOF
# API UI Verification Run $RUN_ID

Date used: $DOC_DATE
Location: $LOCATION
Base URL: $BASE_URL

## Master data

- Customer: $CUSTOMER_NAME
  - Customer ID: $CUSTOMER_ID
  - Reference: $CUSTOMER_REF
- Supplier: $SUPPLIER_NAME
  - Supplier ID: $SUPPLIER_ID
  - Reference: $SUPPLIER_REF
- Item: $ITEM_NAME
  - Stock ID: $STOCK_ID

## Purchasing workflow

- Purchase Order: $PO_REF
  - API ID: $PO_ID
- Purchase Receipt / GRN: $GRN_REF
  - API ID: $GRN_ID
- Supplier Invoice: $SUPP_INV_REF
  - API ID: $SUPP_INV_ID
  - Supplier invoice reference: Supplier invoice $RUN_ID
- Supplier Payment: $SP_REF
  - API ID: $SP_ID
  - Amount: 68.75

## Sales workflow

- Sales Order: $SO_REF
  - API ID: $SO_ID
- Sales Delivery: $DN_REF
  - API ID: $DN_ID
- Sales Invoice: $INV_REF
  - API ID: $INV_ID
- Customer Payment: $CP_REF
  - API ID: $CP_ID
  - Amount: 55.00

## GL / stock

- Journal Entry: $JOURNAL_REF
  - API ID: $JOURNAL_ID
- Item purchased quantity: 5
- Item sold quantity: 2
- Expected remaining stock movement net before other adjustments: +3 for $STOCK_ID

## Suggested FrontAccounting UI checks

1. Sales → Sales Order Inquiry: search reference $SO_REF
2. Sales → Customer Transaction Inquiry: search customer $CUSTOMER_NAME or invoice $INV_REF
3. Sales → Customer Payments: search reference $CP_REF
4. Purchases → Purchase Order Inquiry: search reference $PO_REF
5. Purchases → Supplier Transaction Inquiry: search supplier $SUPPLIER_NAME or invoice $SUPP_INV_REF
6. Purchases → Supplier Payments: search reference $SP_REF
7. Items and Inventory → Items: search stock ID $STOCK_ID
8. Items and Inventory → Inventory Item Movements: search stock ID $STOCK_ID
9. Banking and General Ledger → Journal Inquiry: search reference $JOURNAL_REF
EOF

cat "$SUMMARY"
echo
echo "Summary written to: $SUMMARY"
