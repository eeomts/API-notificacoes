#!/bin/bash
# Registra ou atualiza o token FCM de um dispositivo

API_URL="https://sua-api.com"
API_KEY="sua-chave-secreta"

curl -s -X POST "$API_URL/api/tokens" \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "fcm_token": "token-fcm-gerado-pelo-firebase-sdk",
    "platform": "android",
    "user_id": "123",
    "extra": {
      "app_version": "2.1.0",
      "device_model": "Pixel 7"
    }
  }' | python3 -m json.tool
