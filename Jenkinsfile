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
    
    stages {
        stage('Clone current artifacts') {
            steps {
                dir('git-artifacts') {
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
                    sh 'find . -maxdepth 1 -not -path "./git-artifacts" -exec rm -rf {} \\;'
                }
            }
            when { expression { return params.CLEAN_INSTALL } }
        }
        
        stage('Install current project configuration') {
            steps {
                dir('git-creativeshop') {
                    // Update project base
                    checkout([
                        $class: 'GitSCM',
                        branches: [[name: "*/${params.CREATIVESHOP_BRANCH}"]],
                        userRemoteConfigs: [[url: params.CREATIVESHOP_REPO, credentialsId: params.GIT_CREDS]]
                    ])
                }
                
                script {
                    // Install new project base
                    sh 'rsync -avz git-creativeshop/ ${WORKSPACE}/ --exclude .git --exclude .gitignore'
                    // Copy lockfile from previous build for comparison if exists
                    sh '[ -f git-artifacts/composer.lock ] && cp git-artifacts/composer.lock .'
                    // Keep old lockfile for changes comparison
                    sh 'mv composer.lock composer.lock.previous'
                }
            }
        }
    
        stage('Prepare deps for phing if new workspace') {
            steps {
                script {
                    sh '([ -f "auth.json.encrypted" ] && [ ! -f "auth.json" ] && ansible-vault --vault-password-file=~/.raccoon-vault-password --output=auth.json decrypt auth.json.encrypted) || echo "auth.json present, nothing to do"'
                    sh '([ ! -d "vendor" ] && php /usr/local/bin/composer update) || echo "vendor exists, nothing to do"'
                }
            } 
        }
        
        stage('Phing build') {
            steps {
                script {
                    sh 'env'
                    // sh 'vendor/bin/phing ci-build'
                }
            } 
        }
        
        stage('Push artifacts') {
            steps {
                script {
                    // Store build nr for identifcation on server
                    writeFile file: 'pub/BUILD', text: env.BUILD_NUMBER + (new Date()).format('dd.MM.yyyy HH:mm:ss')
                    
                    // Sync new artifacts
                    script {
                        sh "rsync -avz --delete --delete-excluded . git-artifacts --exclude '/git-*' --exclude '.git'  --exclude '/build/' --exclude '/dev/' --exclude '/pub/media/' --exclude '/vendor/creativestyle/theme-*/**' --exclude '/app/etc/env.php' --exclude '/auth.json' --exclude '/var/**' --exclude '/generated/' --exclude 'node_modules/'"
                    }
                    
                    dir ('git-artifacts') {
                        sshagent (credentials: [params.GIT_CREDS]) {
                            sh 'git add . -A'
                            sh 'git commit -m "Build #${BUILD_NUMBER}"'
                            sh 'git push origin HEAD:${ARTIFACT_BRANCH}'
                        }
                    }
                }
            }
        }
    }
}
