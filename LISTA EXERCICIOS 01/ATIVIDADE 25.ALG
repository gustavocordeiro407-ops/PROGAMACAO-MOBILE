algoritmo "ATIVIDADE 25"
var n, maior, menor: real
    i: inteiro
inicio
   escreva("Digite o 1º número: ")
   leia(n)
   maior <- n
   menor <- n

   para i de 2 ate 10 faca
      escreva("Digite o ", i, "º número: ")
      leia(n)
      se n > maior entao
         maior <- n
      fimse
      se n < menor entao
         menor <- n
      fimse
   fimpara
   escreval("Maior: ", maior)
   escreval("Menor: ", menor)
fimalgoritmo