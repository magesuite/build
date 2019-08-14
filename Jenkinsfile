import groovy.json.JsonOutput

def buildUser = currentBuild.rawBuild.getCause(Cause.UserIdCause) ? currentBuild.rawBuild.getCause(Cause.UserIdCause).getUserId() : 'Jenkins'

pipeline {
    agent any;
    
    parameters {
        booleanParam(name: 'CLEAN_INSTALL', defaultValue: false, description: 'Install packages from scratch')
        string(name: 'ARTIFACT_REPO', defaultValue: params.ARTIFACT_REPO, description: 'Artifact git repo URL')
        string(name: 'ARTIFACT_BRANCH', defaultValue: params.ARTIFACT_BRANCH ?: 'master', description: 'Artifact repo branch')
        string(name: 'ARTIFACT_FAILED_BRANCH', defaultValue: params.ARTIFACT_FAILED_BRANCH ?: 'failed', description: 'Artifact repo branch for failed builds')
        string(name: 'ARTIFACT_QUICK_BRANCH', defaultValue: params.ARTIFACT_QUICK_BRANCH ?: 'quick', description: 'Artifact repo branch for quick builds')
        string(name: 'CREATIVESHOP_REPO', defaultValue: params.CREATIVESHOP_REPO ?: 'git@gitlab.creativestyle.pl:m2c/m2c.git', description: 'Project repo URL')
        string(name: 'CREATIVESHOP_BRANCH', defaultValue: params.CREATIVESHOP_BRANCH, description: 'Project repo branch')
        string(name: 'PROJECT_NAME', defaultValue: params.PROJECT_NAME ?: 'creativeshop', description: 'Name of the project')
        string(name: 'SLACK_CHANNEL', defaultValue: params.SLACK_CHANNEL ?: '#m2c', description: 'Slack channel for notifications')
        string(name: 'PHP', defaultValue: params.PHP ?: 'php', description: 'PHP binary')
        booleanParam(name: 'QUICK_BUILD', defaultValue: false, description: 'Skip testing - this build cannot be deployed to prod! Special artifact branch will be used.')
        credentials(name: 'GIT_CREDS', defaultValue: params.GIT_CREDS ?: '1aa37c8c-73f1-4b3c-a2e5-149de20b989c', description: 'Git repo access credentials')
    }
    
    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        disableConcurrentBuilds()
        ansiColor('xterm')
    }

    
    environment {
        BUILD_USER="${buildUser}"
        SKIP_TESTS="${QUICK_BUILD}"
    }
    
    stages {
        stage("Send notifications") {
            steps {
                script {
                    if (params.SLACK_CHANNEL) {
                        slackSend color: '#2D8BF1', channel: params.SLACK_CHANNEL, message: ":gear: " + (QUICK_BUILD ? '[QUICK - No tests!] ' : '') + "Build of *${params.PROJECT_NAME}* has been started. | <${env.BUILD_URL}| Job #${env.BUILD_NUMBER}> \n :pray: Started by _${BUILD_USER}_"
                    }
                }
            }
        }
        
        stage('Clone current artifacts') {
            steps {
                script {
                    if (QUICK_BUILD) {
                        ARTIFACT_BRANCH = ARTIFACT_QUICK_BRANCH
                    }
                }

                dir('artifacts') {
                    checkout([
                        $class: 'GitSCM',
                        extensions: [[$class: 'CloneOption', depth: 1, noTags: false, reference: '', shallow: true]],
                        branches: [[name: "*/${params.ARTIFACT_BRANCH}"]],
                        userRemoteConfigs: [[url: params.ARTIFACT_REPO, credentialsId: params.GIT_CREDS]]
                    ])
                }

                dir('failed_artifacts') {
                    checkout([
                        $class: 'GitSCM',
                        extensions: [[$class: 'CloneOption', depth: 1, noTags: false, reference: '', shallow: true]],
                        branches: [[name: "*/${params.ARTIFACT_FAILED_BRANCH}"]],
                        userRemoteConfigs: [[url: params.ARTIFACT_REPO, credentialsId: params.GIT_CREDS]]
                    ])
                }
            }
        }
        
        stage('Clean workspace') {
            steps {
                script {
                    sh 'rm -rf workspace'
                }
            }
            when { expression { return params.CLEAN_INSTALL } }
        }
        
        stage('Install current project configuration') {
            steps {
                dir('creativeshop') {
                    // Update project base
                    checkout([
                        $class: 'GitSCM',
                        extensions: [[$class: 'CloneOption', depth: 1, noTags: false, reference: '', shallow: true]],
                        branches: [[name: "*/${params.CREATIVESHOP_BRANCH}"]],
                        userRemoteConfigs: [[url: params.CREATIVESHOP_REPO, credentialsId: params.GIT_CREDS]]
                    ])
                }
                
                script {
                    // Create workspace dir if not existing
                    sh '([ -d "workspace"] && mkdir workspace) || true'
                    // Install new project base
                    sh 'rsync -avz creativeshop/ workspace/ --exclude .git --exclude .gitignore'
                    // Copy lockfile from previous build for comparison if exists
                    sh '([ -f artifacts/composer.lock ] && cp artifacts/composer.lock workspace/) || true'
                    // Keep old lockfile for changes comparison
                    sh '([ -f "workspace/composer.lock" ] && mv workspace/composer.lock workspace/composer.lock.previous) || echo "No composer.lock found, strange, any steps skipped before, huh?"'
                }
            }
        }
    
        stage('Prepare deps for phing if new workspace') {
            steps {
                dir('workspace') {
                    script {
                        sh '([ -f "auth.json.encrypted" ] && [ ! -f "auth.json" ] && ansible-vault --vault-password-file=~/.raccoon-vault-password --output=auth.json decrypt auth.json.encrypted) || echo "auth.json present, nothing to do"'
                        sh '([ ! -d "vendor" ] && [ -f "composer.json" ] && ${PHP} /usr/local/bin/composer update) || echo "vendor exists, nothing to do"'
                    }
                }
            } 
        }
        
        stage('Phing build') {
            steps {
                dir('artifacts') {
                    sh '([ ! -d "CHANGELOGS" ] && mkdir "CHANGELOGS") || true'
                }
                
                dir('workspace') {
                    script {
                        // Use global phing if local one does not exist
                        sh '([ -f "vendor/bin/phing" ] && ${PHP} vendor/bin/phing ci-build) || ([ ! -f "vendor/bin/phing" ] && ${PHP} /usr/local/bin/phing ci-build)'
                        // Compute changelog
                        sh '([ -f "composer.lock.previous" ] && php71 /usr/local/bin/composer-changelog composer.lock.previous composer.lock --show-commits --vendor-directory=vendor > "../artifacts/CHANGELOGS/BUILD_${BUILD_NUMBER}") || true'
                    }
                }
            } 
        }
        
        stage('Push artifacts') {
            steps {
                script {
                    // Store build nr for identifcation on server
                    Date buildDate = new Date()
                    
                    writeFile file: 'workspace/pub/BUILD.json', text: JsonOutput.prettyPrint(JsonOutput.toJson([
                        nr: env.BUILD_NUMBER,
                        date: buildDate.format('dd.MM.yyyy HH:mm:ss'),
                        timestamp: buildDate.getTime()
                    ]))
                    
                    // Sync new artifacts
                    script {
                        sh "rsync -az --delete --stats workspace/ artifacts/ --exclude '.git'  --exclude 'CHANGELOGS' --exclude '/build/' --exclude '/dev/' --exclude '/pub/media/' --exclude '/vendor/creativestyle/theme-*/**' --exclude '/app/etc/env.php' --exclude '/auth.json' --exclude '/var/**' --exclude '/app/code/Magento/' --exclude '/generated/'"
                    }
                    
                    dir ('artifacts') {
                        sshagent (credentials: [params.GIT_CREDS]) {
                            sh 'git add . -A'
                            sh 'git commit -m "Build #${BUILD_NUMBER}"'
                            sh 'git push origin HEAD:${ARTIFACT_BRANCH}'
                            sh 'git gc --aggressive'
                        }
                    }
                }
            }
        }
    }
    
    post {
        failure {
            script {
                sh "rsync -az --delete --stats workspace/ failed_artifacts/ --exclude '.git'  --exclude 'CHANGELOGS' --exclude '/build/' --exclude '/dev/' --exclude '/pub/media/' --exclude '/vendor/creativestyle/theme-*/**' --exclude '/app/etc/env.php' --exclude '/auth.json' --exclude '/var/**' --exclude '/app/code/Magento/' --exclude '/generated/'"
            
                dir ('failed_artifacts') {
                    sshagent (credentials: [params.GIT_CREDS]) {
                        sh 'git add . -A'
                        sh 'git commit -m "Failed Build #${BUILD_NUMBER} - DO NOT EVER DEPLOY ME!" || true'
                        sh 'git push origin HEAD:' + params.ARTIFACT_FAILED_BRANCH + ' || true'
                        sh 'git gc --aggressive'
                    }

                    GIT_FAILED_ARTIFACT_COMMIT = sh(returnStdout: true, script: "git rev-parse HEAD").trim()
                }

                if (params.SLACK_CHANNEL) {
                    slackSend color: '#C51B20', channel: params.SLACK_CHANNEL, message: ":heavy_exclamation_mark: Building *${params.PROJECT_NAME}* has failed! | <${env.BUILD_URL}| Job #${env.BUILD_NUMBER}>\n:package: <https://gitlab.creativestyle.pl/creativeshop-build-artifacts/${params.PROJECT_NAME}/commit/${GIT_FAILED_ARTIFACT_COMMIT}| See failed build artifacts>"
                }
            }
        }
        
        success {
            script {
                if (params.SLACK_CHANNEL) {
                    slackSend color: '#29D664', channel: params.SLACK_CHANNEL, message: ":white_check_mark: Build of :package: *${params.PROJECT_NAME}* is a success! | <${env.BUILD_URL}| Job #${env.BUILD_NUMBER}>"
                }
            }
        }
    }
}
