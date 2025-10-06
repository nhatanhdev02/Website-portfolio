#!/bin/bash

# Laravel Admin Backend API - CI/CD Test Script
# This script runs comprehensive API tests in CI/CD environments

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-dev}
TIMEOUT=${2:-60}
MAX_RETRIES=${3:-3}
REPORT_DIR="./reports"

echo -e "${BLUE}🚀 Laravel Admin Backend API - CI/CD Test Runner${NC}"
echo -e "${BLUE}=================================================${NC}"
echo ""

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check prerequisites
check_prerequisites() {
    print_info "Checking prerequisites..."

    # Check Node.js
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed"
        exit 1
    fi

    # Check npm
    if ! command -v npm &> /dev/null; then
        print_error "npm is not installed"
        exit 1
    fi

    # Check Newman
    if ! command -v newman &> /dev/null; then
        print_warning "Newman is not installed. Installing..."
        npm install -g newman newman-reporter-html newman-reporter-htmlextra
    fi

    print_status "Prerequisites check completed"
}

# Wait for API to be ready
wait_for_api() {
    local base_url
    local max_attempts=30
    local attempt=1

    if [ "$ENVIRONMENT" = "dev" ]; then
        base_url="http://127.0.0.1:8000"
    else
        base_url="https://api.nhatanh.dev"
    fi

    print_info "Waiting for API to be ready at $base_url..."

    while [ $attempt -le $max_attempts ]; do
        if curl -s -f "$base_url/api/health" > /dev/null 2>&1; then
            print_status "API is ready!"
            return 0
        fi

        print_info "Attempt $attempt/$max_attempts - API not ready, waiting 5 seconds..."
        sleep 5
        ((attempt++))
    done

    print_error "API is not responding after $max_attempts attempts"
    return 1
}

# Run specific test suite
run_test_suite() {
    local suite_name=$1
    local folder_name=$2
    local retry_count=0

    print_info "Running $suite_name tests..."

    while [ $retry_count -lt $MAX_RETRIES ]; do
        if [ -n "$folder_name" ]; then
            if node run-tests.js "$ENVIRONMENT" --folder="$folder_name" --bail; then
                print_status "$suite_name tests passed"
                return 0
            fi
        else
            if node run-tests.js "$ENVIRONMENT" --bail; then
                print_status "$suite_name tests passed"
                return 0
            fi
        fi

        ((retry_count++))
        if [ $retry_count -lt $MAX_RETRIES ]; then
            print_warning "$suite_name tests failed (attempt $retry_count/$MAX_RETRIES). Retrying in 10 seconds..."
            sleep 10
        fi
    done

    print_error "$suite_name tests failed after $MAX_RETRIES attempts"
    return 1
}

# Generate test report summary
generate_summary() {
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local summary_file="$REPORT_DIR/test-summary-$ENVIRONMENT-$(date '+%Y%m%d-%H%M%S').md"

    print_info "Generating test summary..."

    cat > "$summary_file" << EOF
# Laravel Admin Backend API Test Summary

**Environment:** $ENVIRONMENT
**Timestamp:** $timestamp
**Test Runner:** CI/CD Pipeline

## Test Results

EOF

    # Find the latest JSON report
    local latest_json=$(ls -t "$REPORT_DIR"/test-results-*.json 2>/dev/null | head -n1)

    if [ -n "$latest_json" ] && [ -f "$latest_json" ]; then
        # Extract summary from JSON report using node
        node -e "
            const fs = require('fs');
            const report = JSON.parse(fs.readFileSync('$latest_json', 'utf8'));
            const stats = report.run.stats;

            console.log('### Statistics');
            console.log('');
            console.log('| Metric | Total | Failed |');
            console.log('|--------|-------|--------|');
            console.log('| Requests | ' + stats.requests.total + ' | ' + stats.requests.failed + ' |');
            console.log('| Tests | ' + stats.tests.total + ' | ' + stats.tests.failed + ' |');
            console.log('| Assertions | ' + stats.assertions.total + ' | ' + stats.assertions.failed + ' |');
            console.log('');

            if (report.run.failures && report.run.failures.length > 0) {
                console.log('### Failures');
                console.log('');
                report.run.failures.forEach((failure, index) => {
                    console.log((index + 1) + '. **' + failure.error.name + '**: ' + failure.error.message);
                    if (failure.source && failure.source.name) {
                        console.log('   - Request: ' + failure.source.name);
                    }
                    console.log('');
                });
            } else {
                console.log('### ✅ All Tests Passed!');
                console.log('');
                console.log('No failures detected in this test run.');
            }
        " >> "$summary_file"
    else
        echo "⚠️ No test results found" >> "$summary_file"
    fi

    echo "" >> "$summary_file"
    echo "---" >> "$summary_file"
    echo "*Generated by Laravel Admin Backend API CI/CD Test Runner*" >> "$summary_file"

    print_status "Test summary generated: $summary_file"
}

# Cleanup old reports
cleanup_reports() {
    print_info "Cleaning up old reports..."

    # Keep only the last 10 reports
    if [ -d "$REPORT_DIR" ]; then
        find "$REPORT_DIR" -name "test-results-*.json" -type f | sort -r | tail -n +11 | xargs rm -f
        find "$REPORT_DIR" -name "test-report-*.html" -type f | sort -r | tail -n +11 | xargs rm -f
    fi

    print_status "Cleanup completed"
}

# Main execution
main() {
    print_info "Starting CI/CD test execution for $ENVIRONMENT environment"

    # Create reports directory
    mkdir -p "$REPORT_DIR"

    # Check prerequisites
    check_prerequisites

    # Wait for API (only in dev environment)
    if [ "$ENVIRONMENT" = "dev" ]; then
        if ! wait_for_api; then
            print_error "API health check failed"
            exit 1
        fi
    fi

    # Run test suites in order
    local test_suites=(
        "Authentication:Authentication"
        "Hero Section:Hero Section"
        "About Section:About Section"
        "Services:Services"
        "Projects:Projects"
        "Blog Posts:Blog Posts"
        "Contact Management:Contact Management"
        "System Settings:System Settings"
    )

    local failed_suites=()

    for suite in "${test_suites[@]}"; do
        IFS=':' read -r suite_name folder_name <<< "$suite"

        if ! run_test_suite "$suite_name" "$folder_name"; then
            failed_suites+=("$suite_name")
        fi

        # Small delay between test suites
        sleep 2
    done

    # Generate summary
    generate_summary

    # Cleanup old reports
    cleanup_reports

    # Final status
    if [ ${#failed_suites[@]} -eq 0 ]; then
        print_status "All test suites passed successfully!"
        echo ""
        print_info "Reports available in: $REPORT_DIR"
        exit 0
    else
        print_error "The following test suites failed:"
        for suite in "${failed_suites[@]}"; do
            echo "  - $suite"
        done
        echo ""
        print_info "Check reports in: $REPORT_DIR"
        exit 1
    fi
}

# Handle script arguments
case "$1" in
    --help|-h)
        echo "Usage: $0 [environment] [timeout] [max_retries]"
        echo ""
        echo "Arguments:"
        echo "  environment   dev or prod (default: dev)"
        echo "  timeout       Request timeout in seconds (default: 60)"
        echo "  max_retries   Maximum retry attempts (default: 3)"
        echo ""
        echo "Examples:"
        echo "  $0 dev"
        echo "  $0 prod 120 5"
        echo "  $0 --help"
        exit 0
        ;;
    *)
        main
        ;;
esac
