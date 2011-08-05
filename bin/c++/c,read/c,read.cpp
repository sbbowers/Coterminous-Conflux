// c,read.cpp
//   Used to provide readline support for systems that don't provide the readline php extension
//
// Usage: c,read [<question>]
//      requires tty on stdin, stdout
//      returns commands on stderr
//      command history read from file descriptor 3, if available
//      command history read from file descriptor 4, if available
//
// Compile with: `g++ -o c,read c,read.cpp -static-libgcc -static-libstdc++ -lreadline -lncurses -ldl -static`
// Or: `g++ -o c,read c,read.cpp -lreadline`
//
#include <stdio.h>
#include <stdlib.h>
#include <readline/readline.h>
#include <readline/history.h>
#include <string>
#include <signal.h>

int sig_continue = 0;
std::string prompt;
char * command;

void load_history();
void load_tab_completion();
void read_command();
void sighandler(int sig);


int main(int argc, char *argv[])
{
    load_history();
    load_tab_completion();
    signal(SIGINT, &sighandler);    

    if(argc > 1) // load the prompt if available
        prompt = argv[1];
 
    do 
    {
        read_command();
    } while(sig_continue || command);
 
    free(command);
 
    return 0;
}


void read_command()
{
    command = readline(prompt.c_str());
    if(command)
    {
        sig_continue = 0;
        fprintf(stderr, "%s\n", command);

        if(command[0])
            add_history(command);
    }   
}

void load_history()
{
    char t[1024];
    FILE * fd = fdopen(3, "r");
    if(fd)
    {
        while(fgets(t, 1000, fd))
        {
            t[strcspn(t, "\n")] = '\0'; // remove newline
            add_history(t);
        }
        fclose(fd);
    }   
}
void load_tab_completion()
{
    /*
    FILE * fd;
    char t[1024];
    if(fd = fdopen(4, "r"))
    {
        while(fgets(t, 1000, fd))
        {
            t[strcspn(t, "\n")] = '\0'; // remove newline
            printf("fd1: %s", t);
        }
    }   
    */  
}

void sighandler(int sig)
{
    sig_continue = 1;
    printf("\n%s", prompt.c_str());
}