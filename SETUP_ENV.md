# Environment Setup untuk BrackIt

## Instalasi dan Konfigurasi

### 1. Setup Environment Variables

1. **Copy file .env.example ke .env:**
   ```bash
   cp .env.example .env
   ```

2. **Edit file .env dan masukkan nilai yang sebenarnya:**
   ```
   GROQ_API_KEY=your_actual_groq_api_key_here
   GROQ_API_URL=https://api.groq.com/openai/v1/chat/completions
   
   # Database settings
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=your_password
   DB_NAME=your_database_name
   ```

### 2. Keamanan

- File `.env` **TIDAK AKAN** di-commit ke Git untuk menjaga keamanan
- Semua API keys dan konfigurasi sensitif ada di `.env`
- File `.env.example` adalah template yang aman untuk di-commit

### 3. Penggunaan

Setelah setup `.env`, semua konfigurasi akan otomatis dimuat oleh aplikasi.

### 4. Troubleshooting

Jika muncul error "GROQ_API_KEY is required":
1. Pastikan file `.env` ada di root folder
2. Pastikan `GROQ_API_KEY` sudah diisi dengan nilai yang benar
3. Restart web server setelah mengubah `.env`

## Struktur File

```
BrackIt/
├── .env                 # Environment variables (JANGAN COMMIT)
├── .env.example         # Template environment (AMAN untuk commit)
├── .gitignore          # Git ignore rules
└── PHP/AI/
    ├── config.php      # Konfigurasi utama (sekarang aman)
    └── env_loader.php  # Environment loader
```
