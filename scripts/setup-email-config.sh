#!/bin/bash

# SendPortal Email Configuration Script
# This script helps configure email settings for user management
# Based on: https://sendportal.io/docs/v1/getting-started/configuration-and-setup

set -e

echo "=========================================="
echo "SendPortal Email Configuration"
echo "=========================================="
echo ""
echo "This script helps configure email settings for user management"
echo "(registration, invitations, password resets)."
echo ""

# Get the project directory (parent of scripts folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "Please run setup.sh or manual-setup.sh first."
    exit 1
fi

# Enable registration
echo "Step 1: User Registration"
if ! grep -q "^SENDPORTAL_REGISTER=" .env; then
    read -p "Enable user registration? (y/n): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "SENDPORTAL_REGISTER=true" >> .env
        echo "✓ User registration enabled"
    else
        echo "SENDPORTAL_REGISTER=false" >> .env
        echo "✓ User registration disabled"
    fi
else
    echo "✓ Registration setting already configured"
fi
echo ""

# Password reset
echo "Step 2: Password Reset"
if ! grep -q "^SENDPORTAL_PASSWORD_RESET=" .env; then
    read -p "Enable password resets? (y/n) [default: y]: " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        echo "SENDPORTAL_PASSWORD_RESET=true" >> .env
        echo "✓ Password reset enabled"
    else
        echo "SENDPORTAL_PASSWORD_RESET=false" >> .env
        echo "✓ Password reset disabled"
    fi
else
    echo "✓ Password reset setting already configured"
fi
echo ""

# Mail driver selection
echo "Step 3: Mail Driver Configuration"
echo "Select your mail driver:"
echo "  1) SMTP"
echo "  2) Sendmail"
echo "  3) AWS SES"
echo "  4) Mailgun"
echo "  5) Postmark"
echo ""
read -p "Enter choice [1-5]: " MAIL_CHOICE

case $MAIL_CHOICE in
    1)
        MAIL_MAILER="smtp"
        echo ""
        echo "Configuring SMTP..."
        read -p "SMTP Host: " MAIL_HOST
        read -p "SMTP Port [587]: " MAIL_PORT
        MAIL_PORT=${MAIL_PORT:-587}
        read -p "SMTP Username: " MAIL_USERNAME
        read -s -p "SMTP Password: " MAIL_PASSWORD
        echo ""
        read -p "From Address: " MAIL_FROM_ADDRESS
        read -p "From Name: " MAIL_FROM_NAME
        
        # Update .env
        if grep -q "^MAIL_MAILER=" .env; then
            sed -i.bak "s/^MAIL_MAILER=.*/MAIL_MAILER=$MAIL_MAILER/" .env
        else
            echo "MAIL_MAILER=$MAIL_MAILER" >> .env
        fi
        
        [ ! -z "$MAIL_HOST" ] && (grep -q "^MAIL_HOST=" .env && sed -i.bak "s/^MAIL_HOST=.*/MAIL_HOST=$MAIL_HOST/" .env || echo "MAIL_HOST=$MAIL_HOST" >> .env)
        [ ! -z "$MAIL_PORT" ] && (grep -q "^MAIL_PORT=" .env && sed -i.bak "s/^MAIL_PORT=.*/MAIL_PORT=$MAIL_PORT/" .env || echo "MAIL_PORT=$MAIL_PORT" >> .env)
        [ ! -z "$MAIL_USERNAME" ] && (grep -q "^MAIL_USERNAME=" .env && sed -i.bak "s/^MAIL_USERNAME=.*/MAIL_USERNAME=$MAIL_USERNAME/" .env || echo "MAIL_USERNAME=$MAIL_USERNAME" >> .env)
        [ ! -z "$MAIL_PASSWORD" ] && (grep -q "^MAIL_PASSWORD=" .env && sed -i.bak "s/^MAIL_PASSWORD=.*/MAIL_PASSWORD=$MAIL_PASSWORD/" .env || echo "MAIL_PASSWORD=$MAIL_PASSWORD" >> .env)
        [ ! -z "$MAIL_FROM_ADDRESS" ] && (grep -q "^MAIL_FROM_ADDRESS=" .env && sed -i.bak "s/^MAIL_FROM_ADDRESS=.*/MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS/" .env || echo "MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS" >> .env)
        [ ! -z "$MAIL_FROM_NAME" ] && (grep -q "^MAIL_FROM_NAME=" .env && sed -i.bak "s/^MAIL_FROM_NAME=.*/MAIL_FROM_NAME=$MAIL_FROM_NAME/" .env || echo "MAIL_FROM_NAME=$MAIL_FROM_NAME" >> .env)
        ;;
    2)
        MAIL_MAILER="sendmail"
        read -p "From Address: " MAIL_FROM_ADDRESS
        read -p "From Name: " MAIL_FROM_NAME
        
        if grep -q "^MAIL_MAILER=" .env; then
            sed -i.bak "s/^MAIL_MAILER=.*/MAIL_MAILER=$MAIL_MAILER/" .env
        else
            echo "MAIL_MAILER=$MAIL_MAILER" >> .env
        fi
        
        [ ! -z "$MAIL_FROM_ADDRESS" ] && (grep -q "^MAIL_FROM_ADDRESS=" .env && sed -i.bak "s/^MAIL_FROM_ADDRESS=.*/MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS/" .env || echo "MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS" >> .env)
        [ ! -z "$MAIL_FROM_NAME" ] && (grep -q "^MAIL_FROM_NAME=" .env && sed -i.bak "s/^MAIL_FROM_NAME=.*/MAIL_FROM_NAME=$MAIL_FROM_NAME/" .env || echo "MAIL_FROM_NAME=$MAIL_FROM_NAME" >> .env)
        ;;
    3)
        MAIL_MAILER="ses"
        read -p "AWS Access Key ID: " AWS_ACCESS_KEY_ID
        read -s -p "AWS Secret Access Key: " AWS_SECRET_ACCESS_KEY
        echo ""
        read -p "AWS Default Region [us-east-1]: " AWS_DEFAULT_REGION
        AWS_DEFAULT_REGION=${AWS_DEFAULT_REGION:-us-east-1}
        read -p "From Address: " MAIL_FROM_ADDRESS
        read -p "From Name: " MAIL_FROM_NAME
        
        if grep -q "^MAIL_MAILER=" .env; then
            sed -i.bak "s/^MAIL_MAILER=.*/MAIL_MAILER=$MAIL_MAILER/" .env
        else
            echo "MAIL_MAILER=$MAIL_MAILER" >> .env
        fi
        
        [ ! -z "$AWS_ACCESS_KEY_ID" ] && (grep -q "^AWS_ACCESS_KEY_ID=" .env && sed -i.bak "s/^AWS_ACCESS_KEY_ID=.*/AWS_ACCESS_KEY_ID=$AWS_ACCESS_KEY_ID/" .env || echo "AWS_ACCESS_KEY_ID=$AWS_ACCESS_KEY_ID" >> .env)
        [ ! -z "$AWS_SECRET_ACCESS_KEY" ] && (grep -q "^AWS_SECRET_ACCESS_KEY=" .env && sed -i.bak "s/^AWS_SECRET_ACCESS_KEY=.*/AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ACCESS_KEY/" .env || echo "AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ACCESS_KEY" >> .env)
        [ ! -z "$AWS_DEFAULT_REGION" ] && (grep -q "^AWS_DEFAULT_REGION=" .env && sed -i.bak "s/^AWS_DEFAULT_REGION=.*/AWS_DEFAULT_REGION=$AWS_DEFAULT_REGION/" .env || echo "AWS_DEFAULT_REGION=$AWS_DEFAULT_REGION" >> .env)
        [ ! -z "$MAIL_FROM_ADDRESS" ] && (grep -q "^MAIL_FROM_ADDRESS=" .env && sed -i.bak "s/^MAIL_FROM_ADDRESS=.*/MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS/" .env || echo "MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS" >> .env)
        [ ! -z "$MAIL_FROM_NAME" ] && (grep -q "^MAIL_FROM_NAME=" .env && sed -i.bak "s/^MAIL_FROM_NAME=.*/MAIL_FROM_NAME=$MAIL_FROM_NAME/" .env || echo "MAIL_FROM_NAME=$MAIL_FROM_NAME" >> .env)
        ;;
    4)
        MAIL_MAILER="mailgun"
        read -p "Mailgun Domain: " MAILGUN_DOMAIN
        read -s -p "Mailgun Secret: " MAILGUN_SECRET
        echo ""
        read -p "Mailgun Endpoint [api.mailgun.net]: " MAILGUN_ENDPOINT
        MAILGUN_ENDPOINT=${MAILGUN_ENDPOINT:-api.mailgun.net}
        read -p "From Address: " MAIL_FROM_ADDRESS
        read -p "From Name: " MAIL_FROM_NAME
        
        if grep -q "^MAIL_MAILER=" .env; then
            sed -i.bak "s/^MAIL_MAILER=.*/MAIL_MAILER=$MAIL_MAILER/" .env
        else
            echo "MAIL_MAILER=$MAIL_MAILER" >> .env
        fi
        
        [ ! -z "$MAILGUN_DOMAIN" ] && (grep -q "^MAILGUN_DOMAIN=" .env && sed -i.bak "s/^MAILGUN_DOMAIN=.*/MAILGUN_DOMAIN=$MAILGUN_DOMAIN/" .env || echo "MAILGUN_DOMAIN=$MAILGUN_DOMAIN" >> .env)
        [ ! -z "$MAILGUN_SECRET" ] && (grep -q "^MAILGUN_SECRET=" .env && sed -i.bak "s/^MAILGUN_SECRET=.*/MAILGUN_SECRET=$MAILGUN_SECRET/" .env || echo "MAILGUN_SECRET=$MAILGUN_SECRET" >> .env)
        [ ! -z "$MAILGUN_ENDPOINT" ] && (grep -q "^MAILGUN_ENDPOINT=" .env && sed -i.bak "s/^MAILGUN_ENDPOINT=.*/MAILGUN_ENDPOINT=$MAILGUN_ENDPOINT/" .env || echo "MAILGUN_ENDPOINT=$MAILGUN_ENDPOINT" >> .env)
        [ ! -z "$MAIL_FROM_ADDRESS" ] && (grep -q "^MAIL_FROM_ADDRESS=" .env && sed -i.bak "s/^MAIL_FROM_ADDRESS=.*/MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS/" .env || echo "MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS" >> .env)
        [ ! -z "$MAIL_FROM_NAME" ] && (grep -q "^MAIL_FROM_NAME=" .env && sed -i.bak "s/^MAIL_FROM_NAME=.*/MAIL_FROM_NAME=$MAIL_FROM_NAME/" .env || echo "MAIL_FROM_NAME=$MAIL_FROM_NAME" >> .env)
        ;;
    5)
        MAIL_MAILER="postmark"
        read -s -p "Postmark Token: " POSTMARK_TOKEN
        echo ""
        read -p "From Address: " MAIL_FROM_ADDRESS
        read -p "From Name: " MAIL_FROM_NAME
        
        if grep -q "^MAIL_MAILER=" .env; then
            sed -i.bak "s/^MAIL_MAILER=.*/MAIL_MAILER=$MAIL_MAILER/" .env
        else
            echo "MAIL_MAILER=$MAIL_MAILER" >> .env
        fi
        
        [ ! -z "$POSTMARK_TOKEN" ] && (grep -q "^POSTMARK_TOKEN=" .env && sed -i.bak "s/^POSTMARK_TOKEN=.*/POSTMARK_TOKEN=$POSTMARK_TOKEN/" .env || echo "POSTMARK_TOKEN=$POSTMARK_TOKEN" >> .env)
        [ ! -z "$MAIL_FROM_ADDRESS" ] && (grep -q "^MAIL_FROM_ADDRESS=" .env && sed -i.bak "s/^MAIL_FROM_ADDRESS=.*/MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS/" .env || echo "MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS" >> .env)
        [ ! -z "$MAIL_FROM_NAME" ] && (grep -q "^MAIL_FROM_NAME=" .env && sed -i.bak "s/^MAIL_FROM_NAME=.*/MAIL_FROM_NAME=$MAIL_FROM_NAME/" .env || echo "MAIL_FROM_NAME=$MAIL_FROM_NAME" >> .env)
        ;;
    *)
        echo "Invalid choice. Exiting."
        exit 1
        ;;
esac

echo ""
echo "=========================================="
echo "Email Configuration Complete!"
echo "=========================================="
echo ""
echo "Email settings have been configured for user management."
echo "Note: This is separate from workspace email service configuration."
echo ""

