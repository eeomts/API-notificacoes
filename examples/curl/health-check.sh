#!/bin/bash
# Verifica se a API está no ar (não exige autenticação)

API_URL="https://sua-api.com"

curl -s -X GET "$API_URL/api/health" | python3 -m json.tool
