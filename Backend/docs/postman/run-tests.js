/**
 * Postman Collection Test Runner
 *
 * This script runs the Laravel Admin Backend API collection tests
 * using Newman (Postman CLI) for automated testing.
 *
 * Prerequisites:
 * - npm install -g newman
 * - npm install -g newman-reporter-html
 * - npm install -g newman-reporter-htmlextra
 *
 * Usage:
 * - Development: node run-tests.js dev
 * - Production: node run-tests.js prod
 * - Specific folder: node run-tests.js dev --folder "Authentication"
 * - Verbose output: node run-tests.js dev --verbose
 * - Skip SSL verification: node run-tests.js prod --insecure
 */

const newman = require('newman');
const fs = require('fs');
const { execSync } = require('child_process');

// Configuration
const config = {
    dev: {
        environment: './Laravel-Admin-Backend-Development.postman_environment.json',
        baseUrl: 'http://127.0.0.1:8000',
        timeout: 30000,
        delayRequest: 500
    },
    prod: {
        environment: './Laravel-Admin-Backend-Production.postman_environment.json',
        baseUrl: 'https://api.nhatanh.dev',
        timeout: 60000,
        delayRequest: 1000
    }
};

// Parse command line arguments
const args = process.argv.slice(2);
const env = args[0] || 'dev';
const options = {
    folder: args.find(arg => arg.startsWith('--folder'))?.split('=')[1],
    verbose: args.includes('--verbose'),
    insecure: args.includes('--insecure'),
    bail: args.includes('--bail'),
    iterations: parseInt(args.find(arg => arg.startsWith('--iterations'))?.split('=')[1]) || 1
};

const envConfig = config[env];

if (!envConfig) {
    console.error('Invalid environment. Use "dev" or "prod"');
    console.log('Usage: node run-tests.js [dev|prod] [options]');
    console.log('Options:');
    console.log('  --folder=<folder_name>  Run tests for specific folder only');
    console.log('  --verbose               Enable verbose output');
    console.log('  --insecure             Skip SSL certificate verification');
    console.log('  --bail                 Stop on first test failure');
    console.log('  --iterations=<number>  Number of iterations to run');
    process.exit(1);
}

// Create reports directory if it doesn't exist
const reportsDir = './reports';
if (!fs.existsSync(reportsDir)) {
    fs.mkdirSync(reportsDir, { recursive: true });
}

// Generate timestamp for report files
const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);

// Check if Newman reporters are installed
function checkReporters() {
    try {
        execSync('newman --version', { stdio: 'ignore' });
    } catch (error) {
        console.error('Newman is not installed. Please run: npm install -g newman');
        process.exit(1);
    }

    const reporters = ['cli', 'json'];

    try {
        execSync('newman run --help | grep htmlextra', { stdio: 'ignore' });
        reporters.push('htmlextra');
        console.log('Using htmlextra reporter for enhanced HTML reports');
    } catch (error) {
        try {
            execSync('newman run --help | grep html', { stdio: 'ignore' });
            reporters.push('html');
            console.log('Using standard HTML reporter');
        } catch (error) {
            console.log('HTML reporter not available, using CLI and JSON only');
        }
    }

    return reporters;
}

// Pre-flight checks
function preFlightChecks() {
    console.log(`🚀 Starting API tests for ${env.toUpperCase()} environment`);
    console.log(`📍 Base URL: ${envConfig.baseUrl}`);

    if (options.folder) {
        console.log(`📁 Testing folder: ${options.folder}`);
    }

    if (options.verbose) {
        console.log('📝 Verbose mode enabled');
    }

    // Check if collection file exists
    if (!fs.existsSync('./Laravel-Admin-Backend-API.postman_collection.json')) {
        console.error('❌ Collection file not found: Laravel-Admin-Backend-API.postman_collection.json');
        process.exit(1);
    }

    // Check if environment file exists
    if (!fs.existsSync(envConfig.environment)) {
        console.error(`❌ Environment file not found: ${envConfig.environment}`);
        process.exit(1);
    }

    console.log('✅ Pre-flight checks passed');
}

// Run the tests
function runTests() {
    const reporters = checkReporters();

    const newmanOptions = {
        collection: './Laravel-Admin-Backend-API.postman_collection.json',
        environment: envConfig.environment,
        reporters: reporters,
        reporter: {
            json: {
                export: `${reportsDir}/test-results-${env}-${timestamp}.json`
            }
        },
        insecure: options.insecure || (env === 'dev'),
        timeout: envConfig.timeout,
        delayRequest: envConfig.delayRequest,
        iterationCount: options.iterations,
        bail: options.bail,
        color: 'on',
        verbose: options.verbose
    };

    // Add HTML reporter configuration if available
    if (reporters.includes('htmlextra')) {
        newmanOptions.reporter.htmlextra = {
            export: `${reportsDir}/test-report-${env}-${timestamp}.html`,
            template: 'htmlreqres',
            showOnlyFails: false,
            testPaging: true,
            browserTitle: `Laravel Admin Backend API Tests - ${env.toUpperCase()}`,
            title: `API Test Results - ${env.toUpperCase()} Environment`,
            titleSize: 4,
            omitHeaders: false,
            skipHeaders: 'Authorization',
            omitRequestBodies: false,
            omitResponseBodies: false,
            hideRequestBody: [],
            hideResponseBody: [],
            showEnvironmentData: true,
            skipEnvironmentVars: ['admin_password', 'auth_token'],
            showGlobalData: true,
            skipGlobalVars: [],
            skipSensitiveData: true,
            showMarkdownLinks: true,
            showFolderDescription: true,
            timezone: 'Asia/Ho_Chi_Minh'
        };
    } else if (reporters.includes('html')) {
        newmanOptions.reporter.html = {
            export: `${reportsDir}/test-report-${env}-${timestamp}.html`
        };
    }

    // Add folder filter if specified
    if (options.folder) {
        newmanOptions.folder = options.folder;
    }

    console.log('🧪 Running tests...\n');

    newman.run(newmanOptions, function (err, summary) {
        if (err) {
            console.error('❌ Collection run encountered an error:', err);
            process.exit(1);
        }

        // Print summary
        console.log('\n📊 Test Summary:');
        console.log(`   Total Requests: ${summary.run.stats.requests.total}`);
        console.log(`   Failed Requests: ${summary.run.stats.requests.failed}`);
        console.log(`   Total Tests: ${summary.run.stats.tests.total}`);
        console.log(`   Failed Tests: ${summary.run.stats.tests.failed}`);
        console.log(`   Total Assertions: ${summary.run.stats.assertions.total}`);
        console.log(`   Failed Assertions: ${summary.run.stats.assertions.failed}`);

        if (summary.run.failures && summary.run.failures.length > 0) {
            console.log('\n❌ Test Failures:');
            summary.run.failures.forEach((failure, index) => {
                console.log(`   ${index + 1}. ${failure.error.name}: ${failure.error.message}`);
                if (failure.source && failure.source.name) {
                    console.log(`      Request: ${failure.source.name}`);
                }
            });
        }

        console.log(`\n📁 Reports generated in ${reportsDir}/`);
        console.log(`   JSON: test-results-${env}-${timestamp}.json`);

        if (reporters.includes('htmlextra') || reporters.includes('html')) {
            console.log(`   HTML: test-report-${env}-${timestamp}.html`);
        }

        if (summary.run.stats.tests.failed > 0 || summary.run.stats.assertions.failed > 0) {
            console.log('\n⚠️  Some tests failed. Check the reports for details.');
            process.exit(1);
        } else {
            console.log('\n✅ All tests passed successfully!');
        }
    });
}

// Health check function
function healthCheck() {
    console.log(`🏥 Performing health check on ${envConfig.baseUrl}...`);

    newman.run({
        collection: {
            info: { name: 'Health Check' },
            item: [{
                name: 'API Health Check',
                request: {
                    method: 'GET',
                    url: `${envConfig.baseUrl}/api/health`,
                    header: [{ key: 'Accept', value: 'application/json' }]
                },
                event: [{
                    listen: 'test',
                    script: {
                        exec: [
                            'pm.test("API is accessible", function () {',
                            '    pm.response.to.have.status(200);',
                            '});'
                        ]
                    }
                }]
            }]
        },
        reporters: ['cli'],
        timeout: 10000,
        insecure: options.insecure || (env === 'dev')
    }, function (err, summary) {
        if (err || summary.run.stats.tests.failed > 0) {
            console.log('⚠️  Health check failed. API may not be accessible.');
            console.log('   Proceeding with tests anyway...\n');
        } else {
            console.log('✅ Health check passed. API is accessible.\n');
        }

        runTests();
    });
}

// Main execution
preFlightChecks();

// Add health check option
if (args.includes('--health-check')) {
    healthCheck();
} else {
    runTests();
}

// Export configuration for external use
module.exports = {
    config,
    reportsDir,
    runTests,
    healthCheck
};
