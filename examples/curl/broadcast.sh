#!/bin/bash
# Envia notificação para TODOS os dispositivos ativos
# Use com moderação — envia para toda a base de tokens

API_URL="https://sua-api.com"
API_KEY="sua-chave-secreta"

# Broadcast para todos
curl -s -X POST "$API_URL/api/notifications/broadcast" \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Manutenção programada",
    "body": "O sistema ficará em manutenção às 02h desta madrugada.",
    "data": {
      "type": "maintenance"
    }
  }' | python3 -m json.tool

# Broadcast apenas para Android
# curl -s -X POST "$API_URL/api/notifications/broadcast" \
#   -H "Authorization: Bearer $API_KEY" \
#   -H "Content-Type: application/json" \
#   -d '{
#     "title": "Atualização disponível",
#     "body": "Nova versão disponível na Play Store.",
#     "platform": "android"
#   }' | python3 -m json.tool
