import groovy.json.JsonOutput

def buildCause = currentBuild.rawBuild.getCause(Cause.UserIdCause)
def buildUser = buildCause ? buildCause.getUserId() : 'Jenkins'

pipeline {
    agent any;
    
    parameters {
        booleanParam(name: 'CLEAN_INSTALL', defaultValue: false, description: 'Install packages from scratch')
        string(name: 'ARTIFACT_REPO', defaultValue: params.ARTIFACT_REPO, description: 'Artifact git repo URL')
        string(name: 'ARTIFACT_REPO', defaultValue: params.ARTIFACT_REPO, description: 'Artifact git repo URL')
        string(name: 'ARTIFACT_BRANCH', defaultValue: params.ARTIFACT_BRANCH ?: 'master', description: 'Artifact git repo URL')
        string(name: 'CREATIVESHOP_REPO', defaultValue: params.CREATIVESHOP_REPO ?: 'git@gitlab.creativestyle.pl:m2c/m2c.git', description: 'Project repo URL')
        string(name: 'CREATIVESHOP_BRANCH', defaultValue: params.CREATIVESHOP_BRANCH, description: 'Project repo branch')
        credentials(name: 'GIT_CREDS', defaultValue: params.GIT_CREDS ?: '1aa37c8c-73f1-4b3c-a2e5-149de20b989c', description: 'Git repo access credentials')
    }
    
    environment {
        BUILD_USER="${buildUser}"
    }
    
    stages {
        stage('Clone current artifacts') {
            steps {
                dir('artifacts') {
                    checkout([
                        $class: 'GitSCM',
                        branches: [[name: "*/${params.ARTIFACT_BRANCH}"]],
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
                    sh '([ -f "composer.lock" ] && mv workspace/composer.lock workspace/composer.lock.previous) || echo "No composer.lock found, strange, any steps skipped before, huh?"'
                }
            }
        }
    
        stage('Prepare deps for phing if new workspace') {
            steps {
                dir('workspace') {
                    script {
                        sh '([ -f "auth.json.encrypted" ] && [ ! -f "auth.json" ] && ansible-vault --vault-password-file=~/.raccoon-vault-password --output=auth.json decrypt auth.json.encrypted) || echo "auth.json present, nothing to do"'
                        sh '([ ! -d "vendor" ] && php /usr/local/bin/composer update) || echo "vendor exists, nothing to do"'
                    }
                }
            } 
        }
        
        stage('Phing build') {
            steps {
                dir('workspace') {
                    script {
                        sh 'vendor/bin/phing ci-build'
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
                        sh "rsync -az --delete --stats workspace/ artifacts/ --exclude '.git'  --exclude '/build/' --exclude '/dev/' --exclude '/pub/media/' --exclude '/vendor/creativestyle/theme-*/**' --exclude '/app/etc/env.php' --exclude '/auth.json' --exclude '/var/**' --exclude '/generated/' --exclude 'node_modules/'"
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
}
