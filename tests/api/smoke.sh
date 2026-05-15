#!/usr/bin/env bash
set -euo pipefail

BASE_URL=${BASE_URL:-http://localhost:8080/modules/api/public/index.php/v1}
API_COMPANY=${API_COMPANY:-0}
API_USER=${API_USER:-api_test}
API_PASSWORD=${API_PASSWORD:-ApiTest!2026}
TMP_DIR=${TMP_DIR:-/tmp/frontaccounting-api-smoke}
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
  echo "$code $endpoint"
  [[ $code =~ ^2 ]] || { cat "$output" >&2; fail "$method /$endpoint returned HTTP $code"; }
}

request_public() {
  local method=$1 endpoint=$2 output=$TMP_DIR/response.json code
  code=$(curl -sS -o "$output" -w '%{http_code}' -X "$method" "$BASE_URL/$endpoint")
  echo "$code $endpoint"
  [[ $code =~ ^2 ]] || { cat "$output" >&2; fail "$method /$endpoint returned HTTP $code"; }
}

json_get() {
  python3 -c "import json,sys; data=json.load(open(sys.argv[1])); cur=data
for part in sys.argv[2].split('.'):
    cur=cur[part] if isinstance(cur,dict) else cur[int(part)]
print(cur)" "$1" "$2"
}

info "health"
request_public GET health
python3 - <<PY || fail "health response invalid"
import json
p=json.load(open('$TMP_DIR/response.json'))
assert p['success'] is True
assert p['data']['frontAccounting'] == '2.4.20'
PY

info "login"
LOGIN_BODY=$(printf '{"company":%s,"username":"%s","password":"%s"}' "$API_COMPANY" "$API_USER" "$API_PASSWORD")
LOGIN_CODE=$(curl -sS -o "$TMP_DIR/login.json" -w '%{http_code}' -H 'Content-Type: application/json' -d "$LOGIN_BODY" "$BASE_URL/auth/login")
[[ "$LOGIN_CODE" == 200 ]] || { cat "$TMP_DIR/login.json" >&2; fail "login returned HTTP $LOGIN_CODE"; }
TOKEN=$(json_get "$TMP_DIR/login.json" data.accessToken)
[[ -n "$TOKEN" ]] || fail "empty token"

info "read endpoints"
for ep in companies items/categories items customers suppliers currencies exchange-rates bank-accounts gl/accounts tax/types tax/groups locations sales/orders sales/invoices purchase/orders customer-payments supplier-payments; do
  request GET "$ep"
  python3 - <<PY || fail "$ep did not return success JSON"
import json
p=json.load(open('$TMP_DIR/response.json'))
assert p['success'] is True
assert 'data' in p
PY
done

TS=${SMOKE_TS:-$(date +%H%M%S)}

info "create item category"
CATEGORY_BODY=$(cat <<JSON
{"description":"API Smoke Category $TS","taxTypeId":1,"salesAccount":"4050","cogsAccount":"6920","inventoryAccount":"1200","adjustmentAccount":"1205","wipAccount":"6910","units":"each","mbFlag":"B"}
JSON
)
request POST items/categories "$CATEGORY_BODY"
CATEGORY_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$CATEGORY_ID" =~ ^[0-9]+$ ]] || fail "invalid category id: $CATEGORY_ID"

info "update item category"
UPDATED_CATEGORY_BODY=${CATEGORY_BODY/API Smoke Category/API Smoke Category Updated}
request PUT "items/categories/$CATEGORY_ID" "$UPDATED_CATEGORY_BODY"

info "create item"
STOCK_ID="APISMOKE$TS"
ITEM_BODY=$(cat <<JSON
{"stockId":"$STOCK_ID","description":"API Smoke Item $TS","categoryId":1,"taxTypeId":1,"units":"each","mbFlag":"B","salesAccount":"4050","inventoryAccount":"1200","cogsAccount":"6920","adjustmentAccount":"1205","wipAccount":"6910"}
JSON
)
request POST items "$ITEM_BODY"
[[ "$(json_get "$TMP_DIR/response.json" data.stockId)" == "$STOCK_ID" ]] || fail "item stock id mismatch"

info "update item"
UPDATED_ITEM_BODY=${ITEM_BODY/API Smoke Item/API Smoke Item Updated}
request PUT "items/$STOCK_ID" "$UPDATED_ITEM_BODY"

info "create customer"
CUSTOMER_BODY=$(cat <<JSON
{"name":"API Smoke Customer $TS","reference":"APISMOKEC$TS","currency":"USD","address":"Smoke Test Address","creditStatus":1,"paymentTerms":4,"salesType":1,"creditLimit":1000}
JSON
)
request POST customers "$CUSTOMER_BODY"
CUSTOMER_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$CUSTOMER_ID" =~ ^[0-9]+$ ]] || fail "invalid customer id: $CUSTOMER_ID"

info "update customer"
UPDATED_CUSTOMER_BODY=${CUSTOMER_BODY/API Smoke Customer/API Smoke Customer Updated}
request PUT "customers/$CUSTOMER_ID" "$UPDATED_CUSTOMER_BODY"

info "create supplier"
SUPPLIER_BODY=$(cat <<JSON
{"name":"API Smoke Supplier $TS","reference":"APISMOKES$TS","currency":"USD","payableAccount":"2100","purchaseAccount":"5010","paymentDiscountAccount":"5060","taxGroupId":1}
JSON
)
request POST suppliers "$SUPPLIER_BODY"
SUPPLIER_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$SUPPLIER_ID" =~ ^[0-9]+$ ]] || fail "invalid supplier id: $SUPPLIER_ID"

info "update supplier"
UPDATED_SUPPLIER_BODY=${SUPPLIER_BODY/API Smoke Supplier/API Smoke Supplier Updated}
request PUT "suppliers/$SUPPLIER_ID" "$UPDATED_SUPPLIER_BODY"

info "create journal entry"
JOURNAL_REF="APIJ$TS"
JOURNAL_BODY=$(cat <<JSON
{"date":"2026-05-15","memo":"API smoke journal $TS","reference":"$JOURNAL_REF","lines":[{"account":"1200","amount":10,"memo":"Debit"},{"account":"4050","amount":-10,"memo":"Credit"}]}
JSON
)
request POST journal-entries "$JOURNAL_BODY"
JOURNAL_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$JOURNAL_ID" =~ ^[0-9]+$ ]] || fail "invalid journal id: $JOURNAL_ID"

info "read journal entry"
request GET "journal-entries/$JOURNAL_ID"

info "single-resource reads"
request GET "items/$STOCK_ID"
request GET "items/categories/$CATEGORY_ID"
request GET "customers/$CUSTOMER_ID"
request GET "suppliers/$SUPPLIER_ID"
request GET "bank-accounts"
BANK_ID=$(python3 - <<PY
import json
p=json.load(open("$TMP_DIR/response.json"))
print(p["data"][0]["id"])
PY
)
request GET "bank-accounts/$BANK_ID"
request GET "gl/accounts/1200"

info "pagination"
request GET "items?page=1&perPage=2"
python3 - <<PY || fail "pagination meta invalid"
import json
p=json.load(open('$TMP_DIR/response.json'))
assert p['success'] is True
assert p['meta']['page'] == 1
assert p['meta']['perPage'] == 2
assert p['meta']['count'] <= 2
PY

info "inactive patches"
request PATCH "items/$STOCK_ID/inactive" '{"inactive":true}'
request PATCH "items/$STOCK_ID/inactive" '{"inactive":false}'

info "journal validation"
BAD_JOURNAL_BODY='{"date":"2026-05-15","lines":[{"account":"NOPE","amount":10},{"account":"4050","amount":-10}]}'
BAD_CODE=$(curl -sS -o "$TMP_DIR/bad_journal.json" -w '%{http_code}' -X POST \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d "$BAD_JOURNAL_BODY" "$BASE_URL/journal-entries")
[[ "$BAD_CODE" == 422 ]] || { cat "$TMP_DIR/bad_journal.json" >&2; fail "bad journal expected 422, got $BAD_CODE"; }


info "create sales order"
SALES_ORDER_REF="APISO$TS"
SALES_ORDER_BODY=$(cat <<JSON
{"customerId":$CUSTOMER_ID,"date":"2026-05-15","deliveryDate":"2026-05-15","reference":"$SALES_ORDER_REF","customerReference":"API smoke SO $TS","location":"MEL","lines":[{"stockId":"$STOCK_ID","quantity":1,"price":5,"discount":0,"description":"API Smoke Item $TS"}]}
JSON
)
request POST sales/orders "$SALES_ORDER_BODY"
SALES_ORDER_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$SALES_ORDER_ID" =~ ^[0-9]+$ ]] || fail "invalid sales order id: $SALES_ORDER_ID"
request GET sales/orders

info "create purchase order"
PURCHASE_ORDER_REF="APIPO$TS"
PURCHASE_ORDER_BODY=$(cat <<JSON
{"supplierId":$SUPPLIER_ID,"date":"2026-05-15","deliveryDate":"2026-05-15","reference":"$PURCHASE_ORDER_REF","supplierReference":"API smoke PO $TS","location":"MEL","lines":[{"stockId":"$STOCK_ID","quantity":1,"price":3,"description":"API Smoke Item $TS"}]}
JSON
)
request POST purchase/orders "$PURCHASE_ORDER_BODY"
PURCHASE_ORDER_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$PURCHASE_ORDER_ID" =~ ^[0-9]+$ ]] || fail "invalid purchase order id: $PURCHASE_ORDER_ID"
request GET purchase/orders

info "create purchase receipt"
PURCHASE_RECEIPT_REF="APIGRN$TS"
PURCHASE_RECEIPT_BODY=$(cat <<JSON
{"orderId":$PURCHASE_ORDER_ID,"date":"2026-05-15","reference":"$PURCHASE_RECEIPT_REF","location":"MEL","lines":[{"stockId":"$STOCK_ID","quantity":1}]}
JSON
)
request POST purchase/receipts "$PURCHASE_RECEIPT_BODY"
PURCHASE_RECEIPT_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$PURCHASE_RECEIPT_ID" =~ ^[0-9]+$ ]] || fail "invalid purchase receipt id: $PURCHASE_RECEIPT_ID"

info "create supplier invoice"
SUPPLIER_INVOICE_REF="APISI$TS"
SUPPLIER_INVOICE_BODY=$(cat <<JSON
{"receiptId":$PURCHASE_RECEIPT_ID,"date":"2026-05-15","dueDate":"2026-05-15","reference":"$SUPPLIER_INVOICE_REF","supplierReference":"API smoke SI $TS","lines":[{"stockId":"$STOCK_ID","quantity":1,"price":3}]}
JSON
)
request POST purchase/invoices "$SUPPLIER_INVOICE_BODY"
SUPPLIER_INVOICE_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$SUPPLIER_INVOICE_ID" =~ ^[0-9]+$ ]] || fail "invalid supplier invoice id: $SUPPLIER_INVOICE_ID"


info "create sales delivery"
SALES_DELIVERY_REF="APIDN$TS"
SALES_DELIVERY_BODY=$(cat <<JSON
{"orderId":$SALES_ORDER_ID,"date":"2026-05-15","deliveryDate":"2026-05-15","reference":"$SALES_DELIVERY_REF","location":"MEL","lines":[{"stockId":"$STOCK_ID","quantity":1}]}
JSON
)
request POST sales/deliveries "$SALES_DELIVERY_BODY"
SALES_DELIVERY_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$SALES_DELIVERY_ID" =~ ^[0-9]+$ ]] || fail "invalid sales delivery id: $SALES_DELIVERY_ID"

info "create sales invoice"
SALES_INVOICE_REF="APIINV$TS"
SALES_INVOICE_BODY=$(cat <<JSON
{"deliveryId":$SALES_DELIVERY_ID,"date":"2026-05-15","dueDate":"2026-05-15","reference":"$SALES_INVOICE_REF","lines":[{"stockId":"$STOCK_ID","quantity":1}]}
JSON
)
request POST sales/invoices "$SALES_INVOICE_BODY"
SALES_INVOICE_ID=$(json_get "$TMP_DIR/response.json" data.id)
[[ "$SALES_INVOICE_ID" =~ ^[0-9]+$ ]] || fail "invalid sales invoice id: $SALES_INVOICE_ID"
request GET sales/invoices


info "create customer payment"
CUSTOMER_PAYMENT_REF="APICP$TS"
CUSTOMER_PAYMENT_BODY=$(cat <<JSON
{"customerId":$CUSTOMER_ID,"branchId":0,"bankAccount":1,"date":"2026-05-15","reference":"$CUSTOMER_PAYMENT_REF","amount":1,"memo":"API smoke customer payment $TS"}
JSON
)
request POST customer-payments "$CUSTOMER_PAYMENT_BODY"

info "create supplier payment"
SUPPLIER_PAYMENT_REF="APISP$TS"
SUPPLIER_PAYMENT_BODY=$(cat <<JSON
{"supplierId":$SUPPLIER_ID,"bankAccount":1,"date":"2026-05-15","reference":"$SUPPLIER_PAYMENT_REF","amount":1,"memo":"API smoke supplier payment $TS"}
JSON
)
request POST supplier-payments "$SUPPLIER_PAYMENT_BODY"

info "create stock adjustment"
STOCK_REF="APIS$TS"
STOCK_BODY=$(cat <<JSON
{"location":"MEL","date":"2026-05-15","reference":"$STOCK_REF","memo":"API smoke stock $TS","lines":[{"stockId":"$STOCK_ID","quantity":1,"standardCost":0,"description":"API Smoke Item $TS"}]}
JSON
)
request POST stock-adjustments "$STOCK_BODY"

info "all smoke tests passed"
