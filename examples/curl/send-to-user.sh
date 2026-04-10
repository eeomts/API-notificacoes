#!/bin/bash
# Envia notificação para todos os dispositivos de um usuário

API_URL="https://sua-api.com"
API_KEY="sua-chave-secreta"

curl -s -X POST "$API_URL/api/notifications/send-to-users" \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "123",
    "title": "Sua entrega chegou!",
    "body": "O pedido #1234 foi entregue com sucesso.",
    "data": {
      "order_id": "1234",
      "action": "open_order"
    }
  }' | python3 -m json.tool
