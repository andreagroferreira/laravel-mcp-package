# Publishing Instructions

Follow these steps to publish your Laravel MCP Server package to GitHub and Packagist.

## 1. Initialize Git Repository

```bash
# Navigate to your package directory
cd /Users/andreferreira/Herd/MCPpakacge/packages/laravel-mcp-package

# Initialize a new Git repository
git init

# Add all files to the repository
git add .

# Commit the files
git commit -m "Initial commit"
```

## 2. Create GitHub Repository

1. Go to [GitHub](https://github.com/new)
2. Create a new repository named `laravel-mcp-server`
3. Do not initialize with README, .gitignore, or license files

## 3. Push to GitHub

```bash
# Add the GitHub repository as a remote
git remote add origin https://github.com/wizardingcode/laravel-mcp-server.git

# Push your code to GitHub
git push -u origin main
```

## 4. Register on Packagist

1. If you don't have a Packagist account, create one at [Packagist](https://packagist.org/register/)
2. Log in to Packagist
3. Go to [Submit Package](https://packagist.org/packages/submit)
4. Enter your GitHub repository URL: `https://github.com/wizardingcode/laravel-mcp-server`
5. Click "Check" and then "Submit"

## 5. Configure GitHub Webhook (Optional)

For automatic updates on Packagist when you push to GitHub:

1. Go to your repository on GitHub
2. Click "Settings" > "Webhooks" > "Add webhook"
3. Set Payload URL to: `https://packagist.org/api/github?username=your-packagist-username`
4. Set Content type to: `application/json`
5. Select "Just the push event" 
6. Click "Add webhook"

## 6. Semantic Versioning

Remember to follow semantic versioning for your releases:

```bash
# Create a new tag for your release
git tag -a v1.0.0 -m "First stable release"

# Push the tag to GitHub
git push origin v1.0.0
```

This will automatically update your package on Packagist.

## 7. Verify Installation

Test that your package can be installed via Composer:

```bash
composer require wizardingcode/laravel-mcp-server
```