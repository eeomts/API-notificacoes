#!/bin/bash
# Envia notificação para um dispositivo específico

API_URL="https://sua-api.com"
API_KEY="sua-chave-secreta"

curl -s -X POST "$API_URL/api/notifications/send-to-token" \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "fcm_token": "token-fcm-do-dispositivo",
    "title": "Novo pedido!",
    "body": "Você tem um novo pedido #1234",
    "data": {
      "order_id": "1234",
      "action": "open_order"
    }
  }' | python3 -m json.tool
