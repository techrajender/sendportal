#!/bin/bash

# SendPortal Admin Password Reset Script
# This script resets the password for the admin user

set -e

echo "=========================================="
echo "SendPortal Admin Password Reset"
echo "=========================================="
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    exit 1
fi

# Get email from argument or use default
ADMIN_EMAIL="${1:-admin@unisonwave.com}"

echo "Resetting password for: $ADMIN_EMAIL"
echo ""

# Check if user exists
USER_EXISTS=$(php artisan tinker --execute="
\$user = \App\Models\User::where('email', '$ADMIN_EMAIL')->first();
if (\$user) {
    echo 'exists';
} else {
    echo 'not_found';
}
" 2>/dev/null | tail -1)

if [ "$USER_EXISTS" != "exists" ]; then
    echo "❌ Error: User with email '$ADMIN_EMAIL' not found!"
    exit 1
fi

# Get new password
if [ -z "$2" ]; then
    echo "Enter new password (or press Enter for default 'password'):"
    read -s NEW_PASSWORD
    if [ -z "$NEW_PASSWORD" ]; then
        NEW_PASSWORD="password"
        echo "Using default password: password"
    fi
else
    NEW_PASSWORD="$2"
fi

echo ""
echo "Resetting password..."

# Reset password
php artisan tinker --execute="
\$user = \App\Models\User::where('email', '$ADMIN_EMAIL')->first();
\$user->password = Hash::make('$NEW_PASSWORD');
\$user->save();
echo 'Password reset successfully!';
" 2>/dev/null

echo ""
echo "=========================================="
echo "Password Reset Complete!"
echo "=========================================="
echo ""
echo "Email: $ADMIN_EMAIL"
echo "New Password: $NEW_PASSWORD"
echo ""
echo "⚠️  Please change this password after logging in!"
echo ""

