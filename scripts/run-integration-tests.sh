#!/bin/bash

# Integration Tests Runner Script
# Usage: ./scripts/run-integration-tests.sh [options]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default options
TEST_SUITE="Integration"
VERBOSE=false
COVERAGE=false
FILTER=""
GROUP=""
STOP_ON_FAILURE=false
WATCH=false

# Function to print colored output
print_color() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Function to show help
show_help() {
    echo "Integration Tests Runner"
    echo ""
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  -h, --help              Show this help message"
    echo "  -v, --verbose           Verbose output"
    echo "  -c, --coverage          Generate coverage report"
    echo "  -f, --filter FILTER     Filter tests by name"
    echo "  -g, --group GROUP       Run tests by group"
    echo "  -s, --stop-on-failure   Stop on first failure"
    echo "  -w, --watch             Watch for file changes"
    echo "  --suite SUITE           Test suite to run (default: Integration)"
    echo ""
    echo "Examples:"
    echo "  $0                                    # Run all integration tests"
    echo "  $0 -v -c                             # Run with verbose output and coverage"
    echo "  $0 -f EmailIntegrationTest           # Run only email integration tests"
    echo "  $0 -g email                          # Run email group tests"
    echo "  $0 -s                                # Stop on first failure"
    echo "  $0 --suite Unit                      # Run unit tests"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -f|--filter)
            FILTER="$2"
            shift 2
            ;;
        -g|--group)
            GROUP="$2"
            shift 2
            ;;
        -s|--stop-on-failure)
            STOP_ON_FAILURE=true
            shift
            ;;
        -w|--watch)
            WATCH=true
            shift
            ;;
        --suite)
            TEST_SUITE="$2"
            shift 2
            ;;
        *)
            print_color $RED "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
done

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_color $RED "Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    print_color $RED "Error: PHP is not installed or not in PATH"
    exit 1
fi

# Check if Composer dependencies are installed
if [ ! -d "vendor" ]; then
    print_color $YELLOW "Warning: Composer dependencies not found. Installing..."
    composer install
fi

print_color $BLUE "Running $TEST_SUITE tests..."
echo ""

# Build the PHPUnit command
CMD="php artisan test tests/$TEST_SUITE"

# Add options
if [ "$VERBOSE" = true ]; then
    CMD="$CMD --verbose"
fi

if [ "$COVERAGE" = true ]; then
    CMD="$CMD --coverage"
fi

if [ -n "$FILTER" ]; then
    CMD="$CMD --filter $FILTER"
fi

if [ -n "$GROUP" ]; then
    CMD="$CMD --group $GROUP"
fi

if [ "$STOP_ON_FAILURE" = true ]; then
    CMD="$CMD --stop-on-failure"
fi

# Execute the command
print_color $BLUE "Command: $CMD"
echo ""

if [ "$WATCH" = true ]; then
    print_color $YELLOW "Watching for file changes..."
    # Note: Laravel doesn't have built-in watch, but we can use a simple loop
    while true; do
        $CMD
        print_color $GREEN "Waiting for file changes... (Press Ctrl+C to stop)"
        sleep 2
    done
else
    # Run the tests
    if $CMD; then
        print_color $GREEN "✅ All tests passed!"
        echo ""
        
        if [ "$COVERAGE" = true ]; then
            print_color $BLUE "📊 Coverage report generated in storage/app/coverage/"
        fi
    else
        print_color $RED "❌ Some tests failed!"
        exit 1
    fi
fi

echo ""
print_color $GREEN "Test execution completed!"
