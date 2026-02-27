algoritmo "ATIVIDADE 16"
var a, b, c: real
inicio
   escreva("Lados A, B e C: ")
   leia(a, b, c)
   se (a+b>c) e (a+c>b) e (b+c>a) entao
      escreva("Forma triângulo")
   senao
      escreva("Não forma triângulo")
   fimse
fimalgoritmo