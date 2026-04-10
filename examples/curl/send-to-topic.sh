#!/bin/bash
# Envia notificação para um tópico FCM
# Todos os dispositivos inscritos no tópico receberão a notificação

API_URL="https://sua-api.com"
API_KEY="sua-chave-secreta"

curl -s -X POST "$API_URL/api/notifications/send-to-topic" \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "topic": "promocoes",
    "title": "Oferta especial!",
    "body": "50% de desconto em todos os produtos hoje.",
    "data": {
      "promo_id": "PROMO50",
      "action": "open_store"
    }
  }' | python3 -m json.tool
