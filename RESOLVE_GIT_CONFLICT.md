# Resolve Git Conflict di Production Server

## ❌ Error
```
error: Your local changes to the following files would be overwritten by merge:
        fix-production.sh
Please commit your changes or stash them before you merge.
```

## ✅ Solusi - Pilih Salah Satu:

### Option 1: Stash Local Changes (Recommended)
```bash
# Di server production
cd ~/dev/apbdanalysis2026

# Simpan perubahan lokal sementara
git stash

# Pull dari remote
git pull origin main

# Jika ingin restore perubahan lokal
git stash pop
# Atau jika tidak perlu, buang stash
git stash drop
```

### Option 2: Discard Local Changes (Easier)
```bash
# Di server production
cd ~/dev/apbdanalysis2026

# Reset file yang conflict
git checkout -- fix-production.sh

# Pull dari remote
git pull origin main
```

### Option 3: Force Reset (Clean Slate)
```bash
# Di server production
cd ~/dev/apbdanalysis2026

# Backup file yang mungkin penting
cp .env .env.backup 2>/dev/null || true

# Hard reset ke remote
git fetch origin
git reset --hard origin/main

# Restore .env jika ter-reset
cp .env.backup .env 2>/dev/null || true
```

---

## 🚀 Setelah Git Conflict Resolved

Lanjutkan dengan deployment:

```bash
# Verify files updated
git log --oneline -3

# Run deployment script
chmod +x deploy-to-production.sh
./deploy-to-production.sh
```

---

## ⚠️ Note
File `fix-production.sh` kemungkinan dibuat atau dimodifikasi di server sebelumnya. Versi terbaru dari git repository lebih baik dan sudah complete.

