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
                    
                    // This jenkins crap does not copy hidden files, but we don't need gitignore so it should be fine
                    fileOperations {
                        fileCopyOperation(excludes: '.git,.gitignore', includes: '*', flattenFiles: false, targetLocation: "${WORKSPACE}")
                    }
                }
                
                dir('git-artifacts') {
                    // Copy lockfile from previous build for comparison if exists
                    fileOperations {
                        fileCopyOperation(excludes: '.git,.gitignore', includes: 'composer.lock', flattenFiles: false, targetLocation: "${WORKSPACE}")
                    }
                }
                
                fileOperations {
                    // Keep old lockfile for changes comparison
                    fileRenameOperation('composer.lock', 'composer.lock.old')
                }
            }
        }
    
        stage('Decrypt composer auth') {
            steps {
                script {
                    sh 'ansible-vault --vault-password-file=~/.raccoon-vault-password --output=auth.json decrypt auth.json.encrypted'
                }
            } 
            when { expression { return fileExists('auth.json.encrypted') && !fileExists('auth.json') } }
        }
        
        stage('Install composer deps') {
            steps {
                script {
                    sh 'php /usr/local/bin/composer update'
                }
            } 
            when { expression { return !fileExists('vendor') } }
        }
        
        stage('Phing build') {
            steps {
                script {
                    // sh 'vendor/bin/phing ci-build'
                }
            } 
        }
        
        stage('Push artifacts') {
            steps {
                script {
                    // // Store build nr for identifcation on server
                    // writeFile file: 'pub/BUILD', text: params.BUILD_NUMBER
                    
                    // // Sync new artifacts
                    // script {
                    //     sh 'rsync -avz --delete . git-artifacts --e'
                    // }
                    
                    // dir ('git-artifacts') {
                    //     sshagent (credentials: [params.GIT_CREDS]) {
                    //         sh 'git commit -m "Build #${BUILD_NUMBER}"'
                    //         sh 'git push origin HEAD:${ARTIFACT_BRANCH}'
                    //     }
                    // }
                }
            }
        }
    }
}
